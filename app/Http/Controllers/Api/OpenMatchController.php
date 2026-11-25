<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\OpenMatch;
use App\Models\OpenMatchMessage;
use App\Models\OpenMatchParticipant;
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
            ->filter(fn (OpenMatch $match) => ! $match->has_ended)
            ->map(fn (OpenMatch $match) => $this->matchPayload($match, $user))
            ->values();

        $events = Event::with(['creator.venues', 'creator.venue', 'venue', 'registrations'])
            ->where('status', 'Publikasi')
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->get()
            ->map(fn (Event $event) => $this->eventPayload($event, $user))
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
            'registration_type' => ['nullable', 'in:Individu,Kelompok'],
            'host_team_name' => ['nullable', 'required_if:registration_type,Kelompok', 'string', 'max:150'],
            'host_member_count' => ['nullable', 'required_if:registration_type,Kelompok', 'integer', 'min:1', 'max:1000'],
            'jumlah_pemain' => ['required', 'integer', 'min:2', 'max:99'],
            'biaya_mode' => ['nullable', 'in:Gratis,Patungan'],
            'biaya_per_pemain' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'level' => ['nullable', 'string', 'max:50'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'deskripsi' => ['nullable', 'string', 'max:1500'],
        ]);

        $registrationType = $data['registration_type'] ?? OpenMatch::REGISTRATION_INDIVIDU;

        $booking = Booking::with('lapangan.venue')
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

        $biayaMode = $data['biaya_mode'] ?? ((int) ($data['biaya_per_pemain'] ?? 0) > 0 ? 'Patungan' : 'Gratis');
        $biayaPerPemain = $biayaMode === 'Patungan'
            ? (int) ($data['biaya_per_pemain'] ?? 0)
            : null;

        if ($biayaMode === 'Patungan' && $biayaPerPemain <= 0) {
            $biayaPerPemain = (int) ceil(((int) $booking->total_price) / max(1, (int) $data['jumlah_pemain']));
        }

        $match = OpenMatch::create([
            'booking_id' => $booking->id,
            'title' => $data['title'],
            'jenis' => $booking->lapangan?->jenis ?? 'Futsal',
            'registration_type' => $registrationType,
            'tanggal' => $booking->booking_date,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'jumlah_pemain' => $data['jumlah_pemain'],
            'jumlah_bergabung' => 1,
            'deskripsi' => $data['deskripsi'] ?? null,
            'biaya_per_pemain' => $biayaPerPemain,
            'level' => $data['level'] ?? null,
            'contact_phone' => $data['contact_phone'] ?: $user->phone,
            'status' => 'Open',
        ]);

        OpenMatchParticipant::insertOrIgnore([
            'open_match_id' => $match->id,
            'user_id' => $user->id,
            'role' => 'Host',
            'team_name' => $registrationType === OpenMatch::REGISTRATION_KELOMPOK
                ? ($data['host_team_name'] ?? null)
                : null,
            'member_count' => $registrationType === OpenMatch::REGISTRATION_KELOMPOK
                ? (int) ($data['host_member_count'] ?? 1)
                : 1,
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

        $data = $request->validate([
            'team_name' => [$openMatch->is_group ? 'required' : 'nullable', 'string', 'max:150'],
            'member_count' => [$openMatch->is_group ? 'required' : 'nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $matchStart = $openMatch->tanggal && $openMatch->start_time
            ? Carbon::parse($openMatch->tanggal->toDateString().' '.$openMatch->start_time)
            : null;

        if ($openMatch->status !== 'Open' || $openMatch->has_ended || ! $matchStart || ! $matchStart->isFuture()) {
            return $this->fail('Open Match sudah tidak tersedia.', 422);
        }

        $result = DB::transaction(function () use ($openMatch, $user, $data) {
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

            OpenMatchParticipant::insertOrIgnore([
                'open_match_id' => $match->id,
                'user_id' => $user->id,
                'role' => 'Lawan',
                'team_name' => $match->is_group ? ($data['team_name'] ?? null) : null,
                'member_count' => $match->is_group ? (int) ($data['member_count'] ?? 1) : 1,
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
            return $this->fail('Slot '.$openMatch->capacity_label.' sudah penuh.', 422);
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

    public function registerEvent(Request $request, Event $event)
    {
        $user = $this->currentUser($request);

        $isTeam = ! $event->is_individual;

        $data = $request->validate([
            'team_name' => [$isTeam ? 'required' : 'nullable', 'string', 'max:150'],
            'captain_name' => ['required', 'string', 'max:150'],
            'captain_phone' => ['required', 'string', 'max:30'],
            'member_count' => [$isTeam ? 'required' : 'nullable', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless($event->status === 'Publikasi', 404);

        $event->load(['venue', 'creator.venue', 'creator.venues', 'registrations']);

        if ($event->tanggal && $event->tanggal->lt(today())) {
            return $this->fail('Event ini sudah berlalu.', 422);
        }

        if ($event->registration_deadline && $event->registration_deadline->lt(today())) {
            return $this->fail('Pendaftaran event ini sudah ditutup.', 422);
        }

        if ((int) $event->kuota > 0 && $event->registrations->count() >= (int) $event->kuota) {
            return $this->fail('Kuota '.$event->quota_unit.' untuk event ini sudah penuh.', 422);
        }

        $alreadyRegistered = $event->registrations->firstWhere('user_id', $user->id);
        if ($alreadyRegistered) {
            return $this->fail('Kamu sudah mendaftar untuk event ini.', 422);
        }

        $participantName = $isTeam ? $data['team_name'] : $data['captain_name'];

        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'registration_type' => $event->registration_type,
            'team_name' => $participantName,
            'captain_name' => $data['captain_name'],
            'captain_phone' => $data['captain_phone'],
            'member_count' => $isTeam ? (int) $data['member_count'] : 1,
            'notes' => $data['notes'] ?? null,
            'status' => 'Baru',
        ]);

        $organizerName = $event->creator?->name ?? 'Penyelenggara';
        $phone = $event->whatsapp_number;
        $message = $isTeam
            ? "Halo {$organizerName}, saya {$registration->captain_name} dari tim "
                ."{$registration->team_name}. Saya ingin mendaftar event \"{$event->title}\" "
                ."({$event->jenis}) pada ".optional($event->tanggal)->format('d M Y').'. '
                ."Jumlah anggota: {$registration->member_count} orang. "
                .($registration->notes ? "Catatan: {$registration->notes}. " : '')
                .'Mohon informasi teknis, kelengkapan, dan pembayaran selanjutnya. Terima kasih.'
            : "Halo {$organizerName}, saya {$registration->captain_name}. Saya ingin mendaftar sebagai peserta "
                ."event \"{$event->title}\" ({$event->jenis}) pada ".optional($event->tanggal)->format('d M Y').'. '
                .($registration->notes ? "Catatan: {$registration->notes}. " : '')
                .'Mohon informasi teknis, kelengkapan, dan pembayaran selanjutnya. Terima kasih.';
        $waUrl = $phone
            ? 'https://wa.me/'.$phone.'?text='.urlencode($message)
            : null;

        if ($event->creator) {
            $event->creator->notify(new ArenaUserNotification(
                $isTeam ? 'Pendaftaran Tim Baru' : 'Pendaftaran Peserta Baru',
                ($isTeam ? 'Tim ' : 'Peserta ').$registration->display_name.' mendaftar event '.$event->title.'.',
                route('admin.events.registrations', $event, false)
            ));
        }

        $event->load('registrations.user');

        return $this->ok([
            'event' => $this->eventPayload($event, $user),
            'registration' => [
                'id' => (int) $registration->id,
                'registration_type' => $registration->registration_type,
                'team_name' => $registration->team_name,
                'display_name' => $registration->display_name,
                'captain_name' => $registration->captain_name,
                'captain_phone' => $registration->captain_phone,
                'member_count' => (int) $registration->member_count,
                'status' => $registration->status,
            ],
            'whatsapp_url' => $waUrl,
        ], 'Pendaftaran berhasil disimpan. Lanjutkan konfirmasi ke WhatsApp penyelenggara.', 201);
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
