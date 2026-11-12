<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\OpenMatchMessage;
use App\Models\Payment;
use App\Models\Redemption;
use App\Models\Review;
use App\Models\Reward;
use App\Models\User;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\FonnteWhatsAppService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Notifications\ArenaUserNotification;

class UserController extends Controller
{
    private function currentUser(Request $request): User
    {
        return User::findOrFail(
            $request->session()->get('admin_user_id')
        );
    }

    private function optionalUser(Request $request): ?User
    {
        return User::whereKey($request->session()->get('admin_user_id'))
            ->where('role', 'user')
            ->where('status', 'Aktif')
            ->first();
    }

    /**
     * Memeriksa apakah interval booking berbenturan
     * dengan perpanjangan yang masih menunggu pembayaran.
     */
    private function hasPendingExtensionOverlap(
        int $lapanganId,
        string $bookingDate,
        string $startTime,
        string $endTime
    ): bool {
        return BookingExtension::where('status', 'Menunggu')
            ->where('previous_end_time', '<', $endTime)
            ->where('new_end_time', '>', $startTime)
            ->whereHas('booking', function ($query) use (
                $lapanganId,
                $bookingDate
            ) {
                $query->where('lapangan_id', $lapanganId)
                    ->whereDate('booking_date', $bookingDate)
                    ->whereIn('status', ['Menunggu', 'DP', 'Lunas']);
            })
            ->exists();
    }

    public function home(Request $request): View
    {
        $user = $this->currentUser($request);

        $courts = Lapangan::where('status', 'Tersedia')
            ->latest()
            ->get();

        $venueCount = Venue::where('status', 'Aktif')->count();

        $venues = Venue::withCount([
            'lapangans' => fn ($q) => $q->where('status', 'Tersedia')
        ])
            ->where('status', 'Aktif')
            ->latest()
            ->take(3)
            ->get();

        $bookings = Booking::with('lapangan')
            ->where('user_id', $user->id)
            ->latest()
            ->take(4)
            ->get();

        $matches = OpenMatch::with('booking.lapangan')
            ->where('status', 'Open')
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->take(4)
            ->get();

        return view('user.home', compact(
            'user',
            'courts',
            'venues',
            'venueCount',
            'bookings',
            'matches'
        ));
    }

   
public function courts(Request $request): View
{
    $user = $this->optionalUser($request);

    // Ambil lapangan beserta data venue.
    $query = Lapangan::with('venue')
        ->where('status', 'Tersedia')
        ->where(function ($venueQuery) {
            $venueQuery->whereNull('venue_id')
                ->orWhereHas('venue', function ($venue) {
                    $venue->where('status', 'Aktif');
                });
        });

    // Pencarian berdasarkan nama, lokasi, atau jenis olahraga.
    if ($request->filled('q')) {
        $query->where(function ($q) use ($request) {
            $q->where('nama', 'like', '%' . $request->q . '%')
                ->orWhere('lokasi', 'like', '%' . $request->q . '%')
                ->orWhere('jenis', 'like', '%' . $request->q . '%');
        });
    }

    // Filter berdasarkan jenis olahraga.
    if ($request->filled('jenis')) {
        $query->where('jenis', $request->jenis);
    }

    $courts = $query->latest()->get();

    // Periksa koordinat lokasi pengguna.
    if (
        $request->filled('lat') &&
        $request->filled('lng') &&
        is_numeric($request->lat) &&
        is_numeric($request->lng)
    ) {
        $lat = (float) $request->lat;
        $lng = (float) $request->lng;

        // Pastikan koordinat pengguna valid.
        if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {

            $courts = $courts->map(function ($court) use ($lat, $lng) {

                $court->distance_km = null;

                // Prioritas koordinat dari venue.
                $latitude = $court->venue?->latitude;
                $longitude = $court->venue?->longitude;

                // Jika koordinat venue tidak lengkap,
                // gunakan koordinat lapangan.
                if (
                    !is_numeric($latitude) ||
                    !is_numeric($longitude)
                ) {
                    $latitude = $court->latitude;
                    $longitude = $court->longitude;
                }

                // Hitung jarak jika koordinat tersedia.
                if (
                    is_numeric($latitude) &&
                    is_numeric($longitude)
                ) {
                    $latitude = (float) $latitude;
                    $longitude = (float) $longitude;

                    // Pastikan koordinat tujuan valid.
                    if (
                        $latitude >= -90 &&
                        $latitude <= 90 &&
                        $longitude >= -180 &&
                        $longitude <= 180
                    ) {
                        $dLat = deg2rad($latitude - $lat);
                        $dLng = deg2rad($longitude - $lng);

                        // Rumus Haversine.
                        $a = sin($dLat / 2) ** 2
                            + cos(deg2rad($lat))
                            * cos(deg2rad($latitude))
                            * sin($dLng / 2) ** 2;

                        $court->distance_km = 6371 * 2 * atan2(
                            sqrt($a),
                            sqrt(max(0, 1 - $a))
                        );
                    }
                }

                return $court;

            })->sortBy(
                fn ($court) => $court->distance_km ?? INF
            )->values();
        }
    }

    return view('user.courts', compact('user', 'courts'));
}


    public function courtDetail(
        Request $request,
        Lapangan $lapangan
    ): View {
        $user = $this->optionalUser($request);

        $lapangan->loadMissing('venue');

        abort_unless(
            $lapangan->status === 'Tersedia' &&
            (!$lapangan->venue_id || $lapangan->venue?->status === 'Aktif'),
            404
        );

        $bookingDraft = $request->session()->pull('booking.pending', []);

        return view(
            'user.court-detail',
            compact('user', 'lapangan', 'bookingDraft')
        );
    }

    public function bookingStore(
        Request $request,
        Lapangan $lapangan
    ): RedirectResponse {
        $user = $this->currentUser($request);

        $lapangan->loadMissing('venue');

        abort_unless(
            $lapangan->status === 'Tersedia' &&
            (!$lapangan->venue_id || $lapangan->venue?->status === 'Aktif'),
            422,
            'Lapangan tidak tersedia atau venue belum aktif.'
        );

        $data = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'hours' => ['required', 'integer', 'min:1', 'max:8'],
            'payment_method' => ['required', Rule::in(['Midtrans', 'Cash'])],
        ]);

        if (
            $data['booking_date'] === today()->toDateString() &&
            strtotime($data['start_time']) <= strtotime(now()->format('H:i'))
        ) {
            return back()->withErrors([
                'start_time' => 'Untuk booking hari ini, jam mulai harus masih akan datang.',
            ])->withInput();
        }

        $start = strtotime($data['start_time']);
        $end = $start + ((int) $data['hours'] * 3600);
        $endTime = date('H:i', $end);

        if ($endTime <= $data['start_time']) {
            return back()->withErrors([
                'hours' => 'Jam selesai melewati tengah malam. Pilih durasi yang lebih pendek.',
            ])->withInput();
        }

        $overlap = Booking::where('lapangan_id', $lapangan->id)
            ->whereDate('booking_date', $data['booking_date'])
            ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $data['start_time'])
            ->exists();

        $extensionOverlap = $this->hasPendingExtensionOverlap(
            $lapangan->id,
            $data['booking_date'],
            $data['start_time'],
            $endTime
        );

        if ($overlap || $extensionOverlap) {
            return back()->withErrors([
                'start_time' => 'Jadwal tersebut sudah dipesan atau sedang menunggu pembayaran perpanjangan.',
            ])->withInput();
        }

        $booking = DB::transaction(function () use (
            $user,
            $lapangan,
            $data,
            $endTime
        ) {
            Lapangan::whereKey($lapangan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $overlapNow = Booking::where('lapangan_id', $lapangan->id)
                ->whereDate('booking_date', $data['booking_date'])
                ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            $extensionOverlapNow = $this->hasPendingExtensionOverlap(
                $lapangan->id,
                $data['booking_date'],
                $data['start_time'],
                $endTime
            );

            if ($overlapNow || $extensionOverlapNow) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'start_time' => 'Jadwal tersebut baru saja dipesan pengguna lain. Pilih jam lain.',
                ]);
            }

            $totalPrice = (int) $lapangan->harga * (int) $data['hours'];
            $method = $data['payment_method'];

            $booking = Booking::create([
                'user_id' => $user->id,
                'lapangan_id' => $lapangan->id,
                'payment_method' => $method,
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $endTime,
                'hours' => (int) $data['hours'],
                'total_price' => $totalPrice,
                'status' => 'Menunggu',
            ]);

            Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'method' => $method,
                'amount' => $totalPrice,
                'status' => 'Menunggu',
            ]);

            $user->notify(new ArenaUserNotification(
                'Booking Berhasil Dibuat',
                'Booking lapangan ' . ($lapangan->nama ?? '') .
                    ' untuk ' . $data['booking_date'] .
                    ' berhasil dibuat dan menunggu pembayaran/konfirmasi.',
                route('user.payments', [], false)
            ));

            $venueOwner = $lapangan->venue?->owner
                ?? User::find($lapangan->owner_admin_id);

            if ($venueOwner && $venueOwner->role === 'venue_admin') {
                $venueOwner->notify(new ArenaUserNotification(
                    'Booking Baru Masuk',
                    'Ada booking baru untuk lapangan ' .
                        ($lapangan->nama ?? '-') .
                        ' pada ' . $data['booking_date'] . '.',
                    route('admin.bookings', [], false)
                ));
            }

            return $booking;
        });

        if ($data['payment_method'] === 'Cash') {
            return redirect()
                ->route('user.payments')
                ->with(
                    'success',
                    'Booking berhasil dibuat. Pembayaran tunai dilakukan di venue dan harus dikonfirmasi oleh Admin Venue.'
                );
        }

        return redirect()
            ->route('user.midtrans.start', ['booking' => $booking->id])
            ->with(
                'success',
                'Booking berhasil dibuat. Lanjutkan pembayaran melalui Midtrans.'
            );
    }

    public function bookings(Request $request): View
    {
        $user = $this->currentUser($request);

        $bookings = Booking::with([
            'lapangan',
            'payment',
            'user',
            'extensions.payment',
        ])
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereIn('status', ['Lunas', 'Selesai'])
                    ->orWhereHas('payment', function ($paymentQuery) {
                        $paymentQuery->whereIn(DB::raw('LOWER(status)'), [
                            'lunas',
                            'selesai',
                            'settlement',
                            'capture',
                            'success',
                        ]);
                    });
            })
            ->latest()
            ->get();

        return view('user.bookings', compact('user', 'bookings'));
    }

    /**
     * Membuat pengajuan perpanjangan booking oleh User.
     * Perubahan jam booking baru dilakukan setelah Midtrans terverifikasi.
     */
    public function bookingExtensionStore(
        Request $request,
        Booking $booking
    ): RedirectResponse {
        $user = $this->currentUser($request);

        abort_unless(
            (int) $booking->user_id === (int) $user->id,
            403
        );

        $data = $request->validate([
            'hours' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        $extension = DB::transaction(function () use (
            $booking,
            $user,
            $data
        ) {
            $lapangan = Lapangan::whereKey($booking->lapangan_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedBooking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->status !== 'Lunas') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'Perpanjangan hanya tersedia untuk booking yang sudah lunas.',
                ]);
            }

            $originalPayment = Payment::where('booking_id', $lockedBooking->id)
                ->whereNull('booking_extension_id')
                ->whereIn(DB::raw('LOWER(status)'), [
                    'lunas',
                    'selesai',
                    'settlement',
                    'capture',
                    'success',
                ])
                ->exists();

            if (! $originalPayment) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'Pembayaran booking awal belum terverifikasi lunas.',
                ]);
            }

            $pendingExtension = BookingExtension::where(
                'booking_id',
                $lockedBooking->id
            )
                ->where('status', 'Menunggu')
                ->exists();

            if ($pendingExtension) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'Masih ada perpanjangan yang menunggu pembayaran.',
                ]);
            }

            $previousEnd = substr(
                (string) $lockedBooking->end_time,
                0,
                5
            );

            $startTimestamp = strtotime($previousEnd);
            $endTimestamp = $startTimestamp + (
                (int) $data['hours'] * 3600
            );

            $newEnd = date('H:i', $endTimestamp);

            if ($newEnd <= $previousEnd) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'Perpanjangan melewati tengah malam. Pilih durasi yang lebih pendek.',
                ]);
            }

            $bookingDate = $lockedBooking->booking_date->toDateString();

            $bookingOverlap = Booking::where(
                'lapangan_id',
                $lockedBooking->lapangan_id
            )
                ->whereDate('booking_date', $bookingDate)
                ->where('id', '!=', $lockedBooking->id)
                ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
                ->where('start_time', '<', $newEnd)
                ->where('end_time', '>', $previousEnd)
                ->exists();

            if ($bookingOverlap) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'Jadwal perpanjangan berbenturan dengan booking lain.',
                ]);
            }

            $extensionOverlap = BookingExtension::where(
                'status',
                'Menunggu'
            )
                ->where('previous_end_time', '<', $newEnd)
                ->where('new_end_time', '>', $previousEnd)
                ->whereHas('booking', function ($query) use (
                    $lockedBooking,
                    $bookingDate
                ) {
                    $query->where(
                        'lapangan_id',
                        $lockedBooking->lapangan_id
                    )
                        ->whereDate('booking_date', $bookingDate)
                        ->where('id', '!=', $lockedBooking->id)
                        ->whereIn('status', ['Menunggu', 'DP', 'Lunas']);
                })
                ->exists();

            if ($extensionOverlap) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'Jadwal tersebut sedang dipesan melalui pengajuan perpanjangan lain.',
                ]);
            }

            $amount = (int) $lapangan->harga * (int) $data['hours'];

            $extension = BookingExtension::create([
                'booking_id' => $lockedBooking->id,
                'user_id' => $user->id,
                'admin_user_id' => null,
                'hours' => (int) $data['hours'],
                'previous_end_time' => $previousEnd,
                'new_end_time' => $newEnd,
                'amount' => $amount,
                'payment_method' => 'Midtrans',
                'status' => 'Menunggu',
                'note' => 'Perpanjangan diajukan oleh pengguna.',
            ]);

            Payment::create([
                'booking_id' => $lockedBooking->id,
                'booking_extension_id' => $extension->id,
                'user_id' => $user->id,
                'method' => 'Midtrans',
                'amount' => $amount,
                'status' => 'Menunggu',
            ]);

            return $extension;
        });

        return redirect()
            ->route('user.booking-extensions.midtrans', $extension->id)
            ->with(
                'success',
                'Pengajuan perpanjangan berhasil dibuat. Silakan lanjutkan pembayaran.'
            );
    }

    public function payments(Request $request): View
    {
        $user = $this->currentUser($request);

        $payments = Payment::with([
            'booking.lapangan',
            'extension',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('user.payments', compact('user', 'payments'));
    }

    public function bookingTicket(
        Request $request,
        Booking $booking
    ): View {
        $user = $this->currentUser($request);

        abort_unless(
            (int) $booking->user_id === (int) $user->id,
            403
        );

        $booking->load(['lapangan', 'payment', 'user']);

        $paymentStatus = strtolower(
            (string) ($booking->payment?->status ?? $booking->status)
        );

        abort_unless(
            in_array($paymentStatus, [
                'lunas',
                'selesai',
                'settlement',
                'capture',
                'success',
            ], true),
            403,
            'Tiket hanya tersedia setelah pembayaran lunas.'
        );

        $secret = (string) config('app.key');

        $ticketCode = 'AS-' .
            str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT) .
            '-' .
            strtoupper(substr(
                hash_hmac(
                    'sha256',
                    'booking:' . $booking->id,
                    $secret
                ),
                0,
                10
            ));

        return view(
            'user.booking-ticket',
            compact('user', 'booking', 'ticketCode')
        );
    }

    public function paymentProof(
        Request $request,
        Payment $payment
    ): RedirectResponse {
        $user = $this->currentUser($request);

        abort_unless(
            (int) $payment->user_id === (int) $user->id,
            403
        );

        return back()->withErrors([
            'payment' => 'Pembayaran ArenaSports diproses otomatis melalui Midtrans. Unggah bukti manual tidak digunakan.',
        ]);
    }

    public function openMatches(Request $request): View
    {
        $user = $this->optionalUser($request);

        $matches = OpenMatch::with('booking.lapangan')
            ->whereIn('status', ['Open', 'Penuh'])
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->get();

        $events = Event::where('status', 'Publikasi')
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->get();

        $eligibleBookings = $user
            ? Booking::with('lapangan')
                ->where('user_id', $user->id)
                ->whereIn('status', ['DP', 'Lunas'])
                ->whereDate('booking_date', '>=', today())
                ->whereDoesntHave('openMatch')
                ->orderBy('booking_date')
                ->get()
            : collect();

        return view('user.open-matches', compact(
            'user',
            'matches',
            'events',
            'eligibleBookings'
        ));
    }

    private function ensureOpenMatchChatAccess(
        OpenMatch $openMatch,
        User $user
    ): void {
        $hostId = (int) Booking::whereKey(
            $openMatch->booking_id
        )->value('user_id');

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

    public function openMatchChat(
        Request $request,
        OpenMatch $openMatch
    ): View {
        $user = $this->currentUser($request);

        abort_unless($user->role === 'user', 403);

        $this->ensureOpenMatchChatAccess($openMatch, $user);

        $messages = OpenMatchMessage::with(
            'user:id,name,profile_photo'
        )
            ->where('open_match_id', $openMatch->id)
            ->oldest()
            ->limit(300)
            ->get();

        return view(
            'user.open-match-chat',
            compact('user', 'openMatch', 'messages')
        );
    }

    public function openMatchChatMessages(
        Request $request,
        OpenMatch $openMatch
    ) {
        $user = $this->currentUser($request);

        abort_unless($user->role === 'user', 403);

        $this->ensureOpenMatchChatAccess($openMatch, $user);

        $after = max(0, (int) $request->query('after', 0));

        $messages = OpenMatchMessage::with(
            'user:id,name,profile_photo'
        )
            ->where('open_match_id', $openMatch->id)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(100)
            ->get();

        return response()->json($messages->map(fn ($m) => [
            'id' => $m->id,
            'message' => $m->message,
            'user_id' => $m->user_id,
            'user_name' => $m->user?->name ?? 'Pemain',
            'created_at' => $m->created_at?->format('H:i'),
            'attachment_url' => $m->attachment_path
                ? Storage::disk('public')->url($m->attachment_path)
                : null,
            'attachment_name' => $m->attachment_name,
        ]));
    }

    public function openMatchChatSend(
        Request $request,
        OpenMatch $openMatch
    ): RedirectResponse {
        $user = $this->currentUser($request);

        abort_unless($user->role === 'user', 403);

        $this->ensureOpenMatchChatAccess($openMatch, $user);

        $validated = $request->validate([
            'message' => [
                'nullable',
                'string',
                'max:2000',
                'required_without:attachment',
            ],
            'attachment' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'required_without:message',
            ],
        ], [
            'attachment.image' => 'Lampiran harus berupa gambar.',
            'attachment.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'attachment.max' => 'Ukuran foto maksimal 5 MB.',
            'attachment.required_without' => 'Tulis pesan atau pilih foto terlebih dahulu.',
        ]);

        $path = $request->file('attachment')
            ?->store('open-match-chat', 'public');

        OpenMatchMessage::create([
            'open_match_id' => $openMatch->id,
            'user_id' => $user->id,
            'message' => trim($validated['message'] ?? ''),
            'attachment_path' => $path,
            'attachment_name' => $request->file('attachment')
                ?->getClientOriginalName(),
        ]);

        return redirect()
            ->route('user.openmatches.chat', $openMatch)
            ->with('success', 'Pesan berhasil dikirim.');
    }

    public function openMatchStore(
        Request $request
    ): RedirectResponse {
        $user = $this->currentUser($request);

        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'jumlah_pemain' => ['required', 'integer', 'min:2', 'max:99'],
            'deskripsi' => ['nullable', 'string', 'max:1500'],
        ]);

        $booking = Booking::where('id', $data['booking_id'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['DP', 'Lunas'])
            ->whereDate('booking_date', '>=', today())
            ->first();

        if (
            ! $booking ||
            ! Carbon::parse(
                $booking->booking_date->toDateString() .
                ' ' . $booking->start_time
            )->isFuture()
        ) {
            return back()->withErrors([
                'booking_id' => 'Open Match hanya dapat dibuat dari booking aktif milik sendiri sebelum jam bermain dimulai.',
            ]);
        }

        $existingMatch = OpenMatch::where(
            'booking_id',
            $booking->id
        )
            ->whereIn('status', ['Open', 'Penuh'])
            ->exists();

        if ($existingMatch) {
            return back()->withErrors([
                'booking_id' => 'Booking ini sudah memiliki Open Match.',
            ]);
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
            'Open Match ' . $match->title . ' berhasil dipublikasikan.',
            route('user.openmatches', [], false)
        ));

        return back()->with(
            'success',
            'Open Match berhasil dibuat.'
        );
    }

    public function joinMatch(
        Request $request,
        OpenMatch $openMatch
    ): RedirectResponse {
        $user = $this->currentUser($request);

        $matchStart = $openMatch->tanggal && $openMatch->start_time
            ? Carbon::parse(
                $openMatch->tanggal->toDateString() .
                ' ' . $openMatch->start_time
            )
            : null;

        abort_unless(
            $openMatch->status === 'Open' &&
            $matchStart &&
            $matchStart->isFuture(),
            422,
            'Open Match sudah tidak tersedia.'
        );

        $result = DB::transaction(function () use (
            $openMatch,
            $user
        ) {
            $match = OpenMatch::whereKey($openMatch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $match->status !== 'Open' ||
                $match->jumlah_bergabung >= $match->jumlah_pemain
            ) {
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

            $host = Booking::with('user')
                ->find($match->booking_id)?->user;

            if ($host && (int) $host->id !== (int) $user->id) {
                $host->notify(new ArenaUserNotification(
                    'Pemain Baru Bergabung',
                    $user->name . ' bergabung dengan Open Match ' .
                        $match->title . '.',
                    route('user.openmatches', [], false)
                ));
            }

            return 'ok';
        });

        if ($result === 'joined') {
            return back()->withErrors([
                'match' => 'Kamu sudah bergabung dengan Open Match ini.',
            ]);
        }

        if ($result === 'full') {
            return back()->withErrors([
                'match' => 'Slot pemain sudah penuh.',
            ]);
        }

        return back()->with(
            'success',
            'Berhasil bergabung ke Open Match.'
        );
    }

    public function rewards(Request $request): View
    {
        $user = $this->currentUser($request);

        $rewards = Reward::where('status', 'Aktif')
            ->orderBy('points_required')
            ->get();

        $redemptions = Redemption::with('reward')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('user.rewards', compact(
            'user',
            'rewards',
            'redemptions'
        ));
    }

    public function redeem(
        Request $request,
        Reward $reward
    ): RedirectResponse {
        $user = $this->currentUser($request);

        if ($reward->status !== 'Aktif') {
            return back()->withErrors([
                'reward' => 'Reward sedang tidak tersedia.',
            ]);
        }

        if ($user->points < $reward->points_required) {
            return back()->withErrors([
                'reward' => 'Poin kamu belum mencukupi.',
            ]);
        }

        DB::transaction(function () use ($user, $reward) {
            $lockedUser = User::whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->points < $reward->points_required) {
                abort(422, 'Poin tidak mencukupi.');
            }

            $lockedUser->decrement(
                'points',
                $reward->points_required
            );

            Redemption::create([
                'user_id' => $lockedUser->id,
                'reward_id' => $reward->id,
                'redeemed_at' => now(),
                'qr_code' => 'AR-' . $lockedUser->id .
                    '-' . $reward->id .
                    '-' . Str::upper(Str::random(6)),
                'status' => 'Berhasil',
            ]);

            $lockedUser->notify(new ArenaUserNotification(
                'Penukaran Reward Berhasil',
                'Reward ' . $reward->title .
                    ' berhasil ditukar. Poin telah dipotong dari saldo kamu.',
                route('user.rewards', [], false)
            ));
        });

        return back()->with(
            'success',
            'Reward berhasil ditukar. Poin telah dipotong dari saldo kamu.'
        );
    }

    public function profile(Request $request): View
    {
        $user = $this->currentUser($request);

        return view('user.profile', compact('user'));
    }

    public function profileUpdate(
        Request $request
    ): RedirectResponse {
        $user = $this->currentUser($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $phone = ! empty($data['phone'])
            ? app(FonnteWhatsAppService::class)
                ->normalizeNumber($data['phone'])
            : null;

        if (
            $phone &&
            User::where('phone', $phone)
                ->where('id', '!=', $user->id)
                ->exists()
        ) {
            return back()->withErrors([
                'phone' => 'Nomor WhatsApp sudah digunakan akun lain.',
            ])->withInput();
        }

        $oldEmail = $user->email;

        $updates = [
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => $phone,
        ];

        if (($updates['email'] ?? null) !== $oldEmail) {
            $updates['email_verified_at'] = null;
        }

        if (! empty($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        if ($request->boolean('remove_photo') && $user->profile_photo) {
            $this->deleteProfilePhoto($user->profile_photo);
            $updates['profile_photo'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            $this->deleteProfilePhoto($user->profile_photo);

            $updates['profile_photo'] = $request
                ->file('profile_photo')
                ->store('profiles', 'public');
        }

        $user->update($updates);

        return back()->with(
            'success',
            'Profil berhasil diperbarui.'
        );
    }

    private function deleteProfilePhoto(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, 'uploads/profiles/')) {
            $legacyPath = public_path($path);

            if (is_file($legacyPath)) {
                @unlink($legacyPath);
            }

            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function reviewStore(
        Request $request,
        Booking $booking
    ): RedirectResponse {
        $user = $this->currentUser($request);

        abort_unless(
            (int) $booking->user_id === (int) $user->id &&
            in_array($booking->status, ['Lunas', 'Selesai']),
            403,
            'Review hanya dapat diberikan untuk booking yang selesai.'
        );

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'message' => ['required', 'string', 'max:1500'],
        ]);

        Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'lapangan_id' => $booking->lapangan_id,
            ],
            $data
        );

        return back()->with(
            'success',
            'Review berhasil dikirim.'
        );
    }
}