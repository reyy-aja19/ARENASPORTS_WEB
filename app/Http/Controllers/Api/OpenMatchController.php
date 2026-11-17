<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Event;
use App\Models\OpenMatch;
use App\Models\OpenMatchMessage;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OpenMatchController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $matches = OpenMatch::with(['booking.lapangan.venue', 'booking.user'])
            ->whereIn('status', ['Open', 'Penuh'])
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->orderBy('start_time')
            ->get()
            ->map(fn (OpenMatch $match) => $this->matchPayload($match, $user))
            ->values();

        $events = Event::with(['creator.venues'])
            ->where('status', 'Publikasi')
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->get()
            ->map(fn (Event $event) => $this->eventPayload($event))
            ->values();

        $eligible = collect();

        if ($user) {
            $eligible = Booking::with(['lapangan.venue', 'payment'])
                ->where('user_id', $user->id)
                ->whereIn('status', ['DP', 'Lunas'])
                ->whereDate('booking_date', '>=', today())
                ->whereDoesntHave('openMatch')
                ->orderBy('booking_date')
                ->get()
                ->filter(fn (Booking $booking) => Carbon::parse(
                    $booking->booking_date?->toDateString().' '.$booking->start_time
                )->isFuture())
                ->map(fn (Booking $booking) => $this->bookingPayload($booking))
                ->values();
        }

        return $this->ok([
            'matches' => $matches,
            'events' => $events,
            'eligible_bookings' => $eligible,
        ]);
    }

    public function show(Request $request, OpenMatch $openMatch)
    {
        $openMatch->load('booking.lapangan.venue');

        return $this->ok([
            'match' => $this->matchPayload($openMatch, $request->user()),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->currentUser($request);

        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'jumlah_pemain' => ['required', 'integer', 'min:2', 'max:99'],
            'deskripsi' => ['nullable', 'string', 'max:1500'],
        ]);

        $booking = Booking::with('lapangan')
            ->where('id', $data['booking_id'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['DP', 'Lunas'])
            ->whereDate('booking_date', '>=', today())
            ->first();

        if (! $booking || ! Carbon::parse(
            $booking->booking_date?->toDateString().' '.$booking->start_time
        )->isFuture()) {
            return $this->fail(
                'Open Match hanya dapat dibuat dari booking aktif milik sendiri sebelum jam bermain dimulai.',
                422
            );
        }

        if (OpenMatch::where('booking_id', $booking->id)->whereIn('status', ['Open', 'Penuh'])->exists()) {
            return $this->fail('Booking ini sudah memiliki Open Match.', 422);
        }

        $match = OpenMatch::create([
            'booking_id' => $booking->id,
            'title' => $data['title'],
            'jenis' => $booking->lapangan?->jenis ?? 'Futsal',
            'tanggal' => $booking->booking_date,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'jumlah_pemain' => $data['jumlah_pemain'],
            'jumlah_bergabung' => 1,
            'deskripsi' => $data['deskripsi'] ?? null,
            'status' => 'Open',
        ]);

        DB::table('open_match_participants')->insertOrIgnore([
            'open_match_id' => $match->id,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new ArenaUserNotification(
            'Open Match Berhasil Dibuat',
            'Open Match '.$match->title.' berhasil dipublikasikan.',
            route('user.openmatches', [], false)
        ));

        $match->load('booking.lapangan.venue');

        return $this->ok([
            'match' => $this->matchPayload($match, $user),
        ], 'Open Match berhasil dibuat.', 201);
    }

    public function join(Request $request, OpenMatch $openMatch)
    {
        $user = $this->currentUser($request);

        $matchStart = $openMatch->tanggal && $openMatch->start_time
            ? Carbon::parse($openMatch->tanggal->toDateString().' '.$openMatch->start_time)
            : null;

        if ($openMatch->status !== 'Open' || ! $matchStart || ! $matchStart->isFuture()) {
            return $this->fail('Open Match sudah tidak tersedia.', 422);
        }

        $result = DB::transaction(function () use ($openMatch, $user) {
            $match = OpenMatch::whereKey($openMatch->id)->lockForUpdate()->firstOrFail();

            if ($match->status !== 'Open' || $match->jumlah_bergabung >= $match->jumlah_pemain) {
                return 'full';
            }

            $alreadyJoined = DB::table('open_match_participants')
                ->where('open_match_id', $match->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyJoined) {
                return 'joined';
            }

            DB::table('open_match_participants')->insert([
                'open_match_id' => $match->id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $match->increment('jumlah_bergabung');
            $match->refresh();

            if ($match->jumlah_bergabung >= $match->jumlah_pemain) {
                $match->update(['status' => 'Penuh']);
            }

            $host = Booking::with('user')->find($match->booking_id)?->user;

            if ($host && (int) $host->id !== (int) $user->id) {
                $host->notify(new ArenaUserNotification(
                    'Pemain Baru Bergabung',
                    $user->name.' bergabung dengan Open Match '.$match->title.'.',
                    route('user.openmatches', [], false)
                ));
            }

            return 'ok';
        });

        if ($result === 'joined') {
            return $this->fail('Kamu sudah bergabung dengan Open Match ini.', 422);
        }

        if ($result === 'full') {
            return $this->fail('Slot pemain sudah penuh.', 422);
        }

        $openMatch->refresh()->load('booking.lapangan.venue');

        return $this->ok([
            'match' => $this->matchPayload($openMatch, $user),
        ], 'Berhasil bergabung ke Open Match.');
    }

    public function messages(Request $request, OpenMatch $openMatch)
    {
        $user = $this->currentUser($request);

        $this->ensureChatAccess($openMatch, $user);

        $after = max(0, (int) $request->query('after', 0));

        $messages = OpenMatchMessage::with('user:id,name,profile_photo')
            ->where('open_match_id', $openMatch->id)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(fn (OpenMatchMessage $message) => $this->messagePayload($message))
            ->values();

        return $this->ok([
            'messages' => $messages,
            'last_id' => $messages->last()['id'] ?? $after,
        ]);
    }

    public function sendMessage(Request $request, OpenMatch $openMatch)
    {
        $user = $this->currentUser($request);

        $this->ensureChatAccess($openMatch, $user);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:message'],
        ]);

        $path = $request->file('attachment')?->store('open-match-chat', 'public');

        $message = OpenMatchMessage::create([
            'open_match_id' => $openMatch->id,
            'user_id' => $user->id,
            'message' => trim((string) ($validated['message'] ?? '')),
            'attachment_path' => $path,
            'attachment_name' => $request->file('attachment')?->getClientOriginalName(),
        ]);

        $message->load('user:id,name,profile_photo');

        return $this->ok([
            'message' => $this->messagePayload($message),
        ], 'Pesan berhasil dikirim.', 201);
    }

    private function ensureChatAccess(OpenMatch $openMatch, $user): void
    {
        $hostId = (int) Booking::whereKey($openMatch->booking_id)->value('user_id');

        $isParticipant = DB::table('open_match_participants')
            ->where('open_match_id', $openMatch->id)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless(
            $hostId === (int) $user->id || $isParticipant,
            403,
            'Obrolan hanya untuk pembuat pertandingan dan pemain yang sudah bergabung.'
        );
    }

    private function messagePayload(OpenMatchMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'message' => $message->message,
            'user_id' => (int) $message->user_id,
            'user_name' => $message->user?->name ?? 'Pemain',
            'user_photo_url' => $message->user?->profile_photo_url,
            'created_at' => $message->created_at?->toIso8601String(),
            'time' => $message->created_at?->format('H:i'),
            'attachment_url' => $this->absoluteImage($message->attachment_path),
            'attachment_name' => $message->attachment_name,
        ];
    }
}
