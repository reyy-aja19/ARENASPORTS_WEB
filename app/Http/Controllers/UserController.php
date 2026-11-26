<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\OpenMatchMessage;
use App\Models\OpenMatchParticipant;
use App\Models\Payment;
use App\Models\Redemption;
use App\Models\Review;
use App\Models\Reward;
use App\Models\User;
use App\Models\Event;
use App\Models\EventRegistration;
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
            ->whereIn('role', ['user', 'venue_admin'])
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

        // Jika akun memiliki pengajuan venue yang masih diproses
        $pendingVenueApp = \App\Models\VenueApplication::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'revision', 'Menunggu', 'Perlu Revisi'])
            ->latest()
            ->first();

        // Jadwal booking mendatang terdekat (aktif)
        $upcomingBooking = Booking::with(['lapangan.venue'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['DP', 'Lunas'])
            ->whereDate('booking_date', '>=', today())
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->first();

        // Event resmi terbaru dari Venue Admin
        $upcomingEvents = Event::with('creator.venue')
            ->where('status', 'Publikasi')
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->take(3)
            ->get();

        $courts = Lapangan::bookable()
            ->with(['venue', 'priceRules'])
            ->latest()
            ->get();

        $venueCount = Venue::where('status', 'Aktif')->count();

        $venues = Venue::withCount([
            'lapangans' => fn ($q) => $q->operational()
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
            'matches',
            'pendingVenueApp',
            'upcomingBooking',
            'upcomingEvents'
        ));
    }

   
    public function courts(Request $request): View
    {
        $user = $this->optionalUser($request);

        if ($request->has('clear_location')) {
            $request->session()->forget(['user_lat', 'user_lng']);

            return redirect()->route('user.courts', $request->except(['clear_location', 'lat', 'lng']));
        }

        if ($request->filled('lat') && $request->filled('lng')) {
            $request->session()->put('user_lat', (float) $request->lat);
            $request->session()->put('user_lng', (float) $request->lng);

            return redirect()->route('user.courts', $request->except(['lat', 'lng']));
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'jenis' => ['nullable', 'in:Futsal,Badminton'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:8'],
            'sort' => ['nullable', 'in:nearest,price_asc,price_desc,rating,popular,newest'],
        ]);

        $date = $filters['date'] ?? null;
        $startTime = $filters['start_time'] ?? null;
        $duration = (int) ($filters['duration'] ?? 1);
        $sort = $filters['sort'] ?? ($date && $startTime ? 'nearest' : 'newest');

        $query = Lapangan::with(['venue', 'priceRules'])
            ->withCount(['bookings' => function ($booking) {
                $booking->whereIn('status', ['Lunas', 'Selesai']);
            }])
            ->withAvg(['reviews' => function ($review) {
                $review->where('is_visible', true);
            }], 'rating')
            ->bookable();

        if ($date && $startTime) {
            $endTime = Carbon::parse($startTime)->addHours($duration)->format('H:i');

            $query->with(['bookings' => function ($booking) use ($date) {
                $booking->whereDate('booking_date', $date)
                    ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
                    ->select('id', 'lapangan_id', 'booking_date', 'start_time', 'end_time');
            }]);
        }

        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($sub) use ($term) {
                $sub->where('nama', 'like', "%{$term}%")
                    ->orWhere('lokasi', 'like', "%{$term}%")
                    ->orWhere('jenis', 'like', "%{$term}%")
                    ->orWhereHas('venue', function ($venue) use ($term) {
                        $venue->where('name', 'like', "%{$term}%")
                            ->orWhere('address', 'like', "%{$term}%")
                            ->orWhere('city', 'like', "%{$term}%");
                    });
            });
        }

        if (!empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }

        $courts = $query->latest()->get();

        $pricing = app(\App\Services\CourtPricingService::class);
        $userLat = $request->session()->get('user_lat');
        $userLng = $request->session()->get('user_lng');

        $courts = $courts->filter(function (Lapangan $court) use (
            $pricing,
            $date,
            $startTime,
            $duration
        ) {
            if ($date && !$pricing->isOperatingDay($court->venue, $date)) {
                return false;
            }

            if ($date && $startTime) {
                if (!$pricing->withinOperatingHours($court->venue, $startTime, $duration)) {
                    return false;
                }

                $endTime = Carbon::parse($startTime)->addHours($duration)->format('H:i');

                foreach (($court->bookings ?? collect()) as $booking) {
                    if ($booking->start_time < $endTime && $booking->end_time > $startTime) {
                        return false;
                    }
                }
            }

            return true;
        })->map(function (Lapangan $court) use ($pricing, $date, $startTime, $duration) {
            $court->effective_price = ($date && $startTime)
                ? $pricing->quote($court, $date, $startTime, $duration)['total']
                : $pricing->minimumPrice($court);

            return $court;
        });

        if (!empty($filters['min_price'])) {
            $courts = $courts->filter(fn ($court) => $court->effective_price >= (int) $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $courts = $courts->filter(fn ($court) => $court->effective_price <= (int) $filters['max_price']);
        }

        $hasLocation = is_numeric($userLat) && is_numeric($userLng);

        $courts = $courts->map(function (Lapangan $court) use ($userLat, $userLng, $hasLocation) {
            $court->distance_km = null;

            if (!$hasLocation) {
                return $court;
            }

            $latitude = $court->effective_latitude;
            $longitude = $court->effective_longitude;

            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                return $court;
            }

            $dLat = deg2rad((float) $latitude - (float) $userLat);
            $dLng = deg2rad((float) $longitude - (float) $userLng);

            $a = sin($dLat / 2) ** 2
                + cos(deg2rad((float) $userLat))
                * cos(deg2rad((float) $latitude))
                * sin($dLng / 2) ** 2;

            $court->distance_km = 6371 * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));

            return $court;
        });

        $courts = match ($sort) {
            'nearest' => $courts->sortBy(fn ($court) => $court->distance_km ?? INF),
            'price_asc' => $courts->sortBy('effective_price'),
            'price_desc' => $courts->sortByDesc('effective_price'),
            'rating' => $courts->sortByDesc(fn ($court) => (float) ($court->reviews_avg_rating ?? 0)),
            'popular' => $courts->sortByDesc('bookings_count'),
            default => $courts->sortByDesc('created_at'),
        };

        $courts = $courts->values();

        return view('user.courts', compact('user', 'courts', 'filters', 'sort', 'date', 'startTime', 'duration'));
    }

public function courtDetail(
        Request $request,
        Lapangan $lapangan
    ): View {
        $user = $this->optionalUser($request);

        $lapangan->loadMissing(['venue', 'priceRules', 'reviews' => function ($q) {
            $q->where('is_visible', true)->with('user')->latest();
        }]);

        abort_unless(
            !$lapangan->venue_id || $lapangan->venue?->status === 'Aktif',
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
            $lapangan->is_bookable,
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

        $pricing = app(\App\Services\CourtPricingService::class);

        if (!$pricing->isOperatingDay($lapangan->venue, $data['booking_date'])) {
            return back()->withErrors([
                'booking_date' => 'Venue tidak beroperasi pada hari tersebut.',
            ])->withInput();
        }

        if (!$pricing->withinOperatingHours($lapangan->venue, $data['start_time'], (int) $data['hours'])) {
            return back()->withErrors([
                'start_time' => 'Jam booking di luar jam operasional venue.',
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

            $totalPrice = app(\App\Services\CourtPricingService::class)
                ->quote($lapangan, $data['booking_date'], $data['start_time'], (int) $data['hours'])['total'];
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
                route('user.bookings', [], false)
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
                ->route('user.bookings')
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

        $allBookings = Booking::with([
            'lapangan.venue',
            'payment',
            'user',
            'extensions.payment',
            'openMatch',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $categoryOf = function (Booking $booking): string {
            $effective = strtolower($booking->effective_status);
            $paymentStatus = strtolower((string) ($booking->payment?->status ?? ''));

            if (in_array($effective, ['dibatalkan', 'batal', 'gagal', 'expired'], true)
                || in_array($paymentStatus, ['gagal', 'failed', 'deny', 'cancel', 'expire', 'expired'], true)) {
                return 'dibatalkan';
            }

            if ($effective === 'selesai') {
                return 'selesai';
            }

            if ($booking->is_paid) {
                return 'lunas';
            }

            return 'menunggu';
        };

        $counts = [
            'total' => $allBookings->count(),
            'menunggu' => $allBookings->filter(fn (Booking $b) => $categoryOf($b) === 'menunggu')->count(),
            'lunas' => $allBookings->filter(fn (Booking $b) => $categoryOf($b) === 'lunas')->count(),
            'selesai' => $allBookings->filter(fn (Booking $b) => $categoryOf($b) === 'selesai')->count(),
            'dibatalkan' => $allBookings->filter(fn (Booking $b) => $categoryOf($b) === 'dibatalkan')->count(),
        ];

        $filter = (string) $request->query('status', 'semua');

        foreach ($allBookings as $item) {
            $item->setAttribute('booking_category', $categoryOf($item));
        }

        $bookings = in_array($filter, ['menunggu', 'lunas', 'selesai', 'dibatalkan'], true)
            ? $allBookings->filter(fn (Booking $b) => $b->getAttribute('booking_category') === $filter)->values()
            : $allBookings;

        return view('user.bookings', compact('user', 'bookings', 'counts', 'filter'));
    }

    /**
     * Membatalkan booking yang belum dibayar.
     */
    public function bookingCancel(Request $request, Booking $booking): RedirectResponse
    {
        $user = $this->currentUser($request);

        abort_unless((int) $booking->user_id === (int) $user->id, 403);

        $booking->load(['payment', 'openMatch', 'lapangan']);

        if (! in_array($booking->effective_status, ['Menunggu', 'DP'], true) || $booking->is_paid) {
            return back()->with('error', 'Booking ini tidak bisa dibatalkan karena sudah lunas atau sudah selesai.');
        }

        // Pastikan Midtrans belum mencatat pembayaran sukses sebelum booking dibatalkan.
        if ($this->midtransSaysPaid($booking->payment)) {
            return back()->with('error', 'Pembayaran booking ini sudah lunas. Muat ulang halaman untuk melihat status terbaru.');
        }

        DB::transaction(function () use ($booking, $user): void {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $alreadyPaid = Payment::where('booking_id', $locked->id)
                ->whereNull('booking_extension_id')
                ->whereIn(DB::raw('LOWER(status)'), ['lunas', 'selesai', 'settlement', 'capture', 'success'])
                ->exists();

            if ($alreadyPaid || ! in_array($locked->effective_status, ['Menunggu', 'DP'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'booking' => 'Booking sudah berubah status dan tidak dapat dibatalkan.',
                ]);
            }

            $locked->status = 'Dibatalkan';
            $locked->save();

            Payment::where('booking_id', $locked->id)
                ->whereNull('booking_extension_id')
                ->update(['status' => 'Gagal']);

            OpenMatch::where('booking_id', $locked->id)
                ->whereIn('status', ['Open', 'Penuh'])
                ->update(['status' => 'Dibatalkan']);

            $user->notify(new ArenaUserNotification(
                'Booking Dibatalkan',
                'Booking lapangan ' . ($locked->lapangan?->nama ?? '') .
                    ' pada ' . optional($locked->booking_date)->format('d M Y') .
                    ' berhasil dibatalkan.',
                route('user.bookings', [], false)
            ));
        });

        return redirect()
            ->route('user.bookings')
            ->with('success', 'Booking berhasil dibatalkan.');
    }

    private function midtransSaysPaid(?Payment $payment): bool
    {
        if (! $payment || strtolower((string) $payment->method) !== 'midtrans') {
            return false;
        }

        $orderId = (string) ($payment->midtrans_order_id ?: $payment->transaction_id);
        $serverKey = (string) config('services.midtrans.server_key');

        if ($orderId === '' || $serverKey === '') {
            return false;
        }

        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = (bool) config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        foreach (array_unique(array_filter([$payment->midtrans_order_id, $payment->transaction_id, $orderId])) as $candidate) {
            try {
                $status = \Midtrans\Transaction::status((string) $candidate);
                $transactionStatus = strtolower((string) ($status->transaction_status ?? ''));
                $fraudStatus = strtolower((string) ($status->fraud_status ?? ''));

                if ($transactionStatus === 'settlement'
                    || ($transactionStatus === 'capture' && in_array($fraudStatus, ['', 'accept'], true))) {
                    return true;
                }

                return false;
            } catch (\Throwable $e) {
                // Coba kandidat order ID berikutnya (kasus DANA memakai transaction_id).
            }
        }

        return false;
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
            $lapangan->loadMissing('venue');
            $pricing = app(\App\Services\CourtPricingService::class);

            if (!$pricing->isOperatingDay($lapangan->venue, $bookingDate)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'Venue tidak beroperasi pada tanggal booking tersebut.',
                ]);
            }

            if (!$pricing->withinOperatingHours($lapangan->venue, $previousEnd, (int) $data['hours'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'Perpanjangan melewati jam operasional venue.',
                ]);
            }

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

            $amount = app(\App\Services\CourtPricingService::class)
                ->quote($lapangan, $bookingDate, $previousEnd, (int) $data['hours'])['total'];

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

        $matches = OpenMatch::with(['booking.lapangan.venue', 'booking.user', 'participants'])
            ->whereIn('status', ['Open', 'Penuh'])
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (OpenMatch $match) => ! $match->has_ended)
            ->values();

        $myHistoryMatches = $user
            ? OpenMatch::with(['booking.lapangan.venue', 'booking.user', 'participants'])
                ->where(function ($query) use ($user) {
                    $query->whereHas('booking', fn ($booking) => $booking->where('user_id', $user->id))
                        ->orWhereHas('participants', fn ($participant) => $participant->where('users.id', $user->id));
                })
                ->where(function ($query) {
                    $query->whereIn('status', ['Selesai', 'Dibatalkan'])
                        ->orWhereDate('tanggal', '<', today());
                })
                ->latest('tanggal')
                ->get()
            : collect();

        $events = Event::with(['creator.venue', 'creator.venues', 'venue', 'registrations.user'])
            ->where('status', 'Publikasi')
            ->whereDate('tanggal', '>=', today()->toDateString())
            ->orderBy('tanggal', 'asc')
            ->get();

        $eventRegistrations = $user
            ? EventRegistration::with('event')
                ->where('user_id', $user->id)
                ->whereIn('event_id', $events->pluck('id'))
                ->latest()
                ->get()
                ->keyBy('event_id')
            : collect();

        $eligibleBookings = $user
            ? Booking::with('lapangan.venue')
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
            'myHistoryMatches',
            'events',
            'eventRegistrations',
            'eligibleBookings'
        ));
    }

    /**
     * Halaman form open match diarahkan ke daftar open match (form ada di sana).
     */
    public function createOpenMatch(): RedirectResponse
    {
        return redirect()->route('user.openmatches', ['form' => 1]);
    }

    private function ensureOpenMatchChatAccess(
        OpenMatch $openMatch,
        User $user
    ): void {
        if ($user->role === 'superadmin') {
            return;
        }

        $openMatch->loadMissing('booking.lapangan.venue');

        $isCourtOwner = false;
        if ($openMatch->booking?->lapangan) {
            $court = $openMatch->booking->lapangan;
            $isCourtOwner = ((int) $court->owner_admin_id === (int) $user->id)
                || ((int) ($court->venue?->owner_admin_id ?? 0) === (int) $user->id);
        }

        $hostId = (int) ($openMatch->booking?->user_id ?? 0);

        $isParticipant = DB::table('open_match_participants')
            ->where('open_match_id', $openMatch->id)
            ->where('user_id', $user->id)
            ->exists();

        // Jika match dibuat oleh admin tanpa booking, atau venue_admin adalah pembuat / pengelola
        $isMatchAdmin = ($user->role === 'venue_admin' && ($isCourtOwner || empty($openMatch->booking_id)));

        abort_unless(
            $hostId === (int) $user->id || $isParticipant || $isCourtOwner || $isMatchAdmin,
            403,
            'Obrolan hanya untuk pembuat pertandingan, pemain yang sudah bergabung, atau pengelola venue.'
        );
    }

    public function openMatchChat(
        Request $request,
        OpenMatch $openMatch
    ): View {
        $user = $this->currentUser($request);

        abort_unless(in_array($user->role, ['user', 'venue_admin', 'superadmin'], true), 403);

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

        abort_unless(in_array($user->role, ['user', 'venue_admin', 'superadmin'], true), 403);

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

        abort_unless(in_array($user->role, ['user', 'venue_admin', 'superadmin'], true), 403);

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
            'registration_type' => ['required', 'in:Individu,Kelompok'],
            'host_team_name' => ['nullable', 'required_if:registration_type,Kelompok', 'string', 'max:150'],
            'host_member_count' => ['nullable', 'required_if:registration_type,Kelompok', 'integer', 'min:1', 'max:1000'],
            'jumlah_pemain' => ['required', 'integer', 'min:2', 'max:99'],
            'biaya_mode' => ['required', 'in:Gratis,Patungan'],
            'biaya_per_pemain' => ['nullable', 'integer', 'min:0', 'max:10000000', 'required_if:biaya_mode,Patungan'],
            'level' => ['nullable', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string', 'max:1500'],
        ]);

        $booking = Booking::with(['lapangan.venue'])
            ->where('id', $data['booking_id'])
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
            ])->withInput();
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
            ])->withInput();
        }

        $biayaPerPemain = $data['biaya_mode'] === 'Patungan'
            ? (int) ($data['biaya_per_pemain'] ?? 0)
            : null;

        if ($data['biaya_mode'] === 'Patungan' && $biayaPerPemain <= 0) {
            $biayaPerPemain = (int) ceil(((int) $booking->total_price) / max(1, (int) $data['jumlah_pemain']));
        }

        $match = OpenMatch::create([
            'booking_id' => $booking->id,
            'title' => $data['title'],
            'jenis' => $booking->lapangan?->jenis ?? 'Futsal',
            'registration_type' => $data['registration_type'],
            'tanggal' => $booking->booking_date,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'jumlah_pemain' => $data['jumlah_pemain'],
            'jumlah_bergabung' => 1,
            'deskripsi' => $data['deskripsi'] ?? null,
            'biaya_per_pemain' => $biayaPerPemain,
            'level' => $data['level'] ?? null,
            'contact_phone' => $user->phone,
            'status' => 'Open',
        ]);

        OpenMatchParticipant::insertOrIgnore([
            'open_match_id' => $match->id,
            'user_id' => $user->id,
            'role' => 'Host',
            'team_name' => $data['registration_type'] === OpenMatch::REGISTRATION_KELOMPOK
                ? ($data['host_team_name'] ?? null)
                : null,
            'member_count' => $data['registration_type'] === OpenMatch::REGISTRATION_KELOMPOK
                ? (int) ($data['host_member_count'] ?? 1)
                : 1,
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

        $data = $request->validate([
            'team_name' => [$openMatch->is_group ? 'required' : 'nullable', 'string', 'max:150'],
            'member_count' => [$openMatch->is_group ? 'required' : 'nullable', 'integer', 'min:1', 'max:1000'],
        ]);

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
            $user,
            $data
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
                'match' => 'Slot ' . $openMatch->capacity_label . ' sudah penuh.',
            ]);
        }

        return back()->with(
            'success',
            'Berhasil bergabung ke Open Match.'
        );
    }

    /**
     * Pendaftaran tim untuk event/turnamen resmi (alur hybrid: simpan data,
     * lalu arahkan ke WhatsApp penyelenggara untuk teknis & pembayaran).
     */
    public function eventRegister(Request $request, Event $event): RedirectResponse
    {
        $user = $this->currentUser($request);

        $isTeam = ! $event->is_individual;

        $data = $request->validate([
            'team_name' => [Rule::requiredIf($isTeam), 'nullable', 'string', 'max:150'],
            'captain_name' => ['required', 'string', 'max:150'],
            'captain_phone' => ['required', 'string', 'max:30'],
            'member_count' => [Rule::requiredIf($isTeam), 'nullable', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'agree' => ['accepted'],
        ]);

        abort_unless($event->status === 'Publikasi', 404);

        $event->load(['venue', 'creator.venue', 'creator.venues']);

        if ($event->tanggal && $event->tanggal->lt(today())) {
            return back()->withErrors(['event' => 'Event ini sudah berlalu.'])->withInput();
        }

        if ($event->registration_deadline && $event->registration_deadline->lt(today())) {
            return back()->withErrors(['event' => 'Pendaftaran event ini sudah ditutup.'])->withInput();
        }

        if ((int) $event->kuota > 0
            && $event->registrations()->count() >= (int) $event->kuota) {
            return back()->withErrors(['event' => 'Kuota '.$event->quota_unit.' untuk event ini sudah penuh.'])->withInput();
        }

        $alreadyRegistered = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyRegistered) {
            return back()->withErrors(['event' => 'Kamu sudah mendaftar untuk event ini. Hubungi penyelenggara untuk perubahan data.']);
        }

        $participantName = $isTeam
            ? $data['team_name']
            : $data['captain_name'];

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

        return back()
            ->with('success', 'Pendaftaran berhasil disimpan. Lanjutkan konfirmasi ke WhatsApp penyelenggara.')
            ->with('event_registration_success', [
                'team_name' => $registration->display_name,
                'captain_name' => $registration->captain_name,
                'event_title' => $event->title,
                'wa_url' => $waUrl,
                'organizer' => $organizerName,
                'is_team' => $isTeam,
            ]);
    }

    public function rewards(Request $request): View
    {
        $user = $this->currentUser($request);

        $rewards = Reward::with(['venue', 'owner'])
            ->where('status', 'Aktif')
            ->orderBy('points_required')
            ->get();

        $redemptions = Redemption::with(['reward.venue', 'reward.owner', 'venue', 'owner'])
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

            $lockedReward = Reward::with(['venue', 'owner'])->whereKey($reward->id)->lockForUpdate()->firstOrFail();

            if ($lockedReward->status !== 'Aktif') {
                abort(422, 'Reward sedang tidak tersedia.');
            }

            if ($lockedUser->points < $lockedReward->points_required) {
                abort(422, 'Poin tidak mencukupi.');
            }

            $lockedUser->decrement(
                'points',
                $lockedReward->points_required
            );

            Redemption::create([
                'user_id' => $lockedUser->id,
                'reward_id' => $lockedReward->id,
                'owner_admin_id' => $lockedReward->owner_admin_id,
                'venue_id' => $lockedReward->venue_id,
                'redeemed_at' => now(),
                'qr_code' => 'AR-' . $lockedUser->id . '-' . $lockedReward->id . '-' . Str::upper(Str::random(6)),
                'status' => 'Berhasil',
            ]);

            $venueLabel = $lockedReward->venue?->name ?: 'ArenaSports';
            $lockedUser->notify(new ArenaUserNotification(
                'Penukaran Reward Berhasil',
                'Reward ' . $lockedReward->title . ' berhasil ditukar. Berlaku di: ' . $venueLabel . '. Poin telah dipotong dari saldo kamu.',
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
        $stats = [
            'bookings' => $user->bookings()->count(),
            'completed_bookings' => $user->bookings()->whereIn('status', ['Dikonfirmasi', 'Selesai'])->count(),
            'redemptions' => \App\Models\Redemption::where('user_id', $user->id)->count(),
            'reviews' => $user->reviews()->count(),
            'member_since' => $user->created_at,
        ];

        return view('user.profile', compact('user', 'stats'));
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

    public function passwordUpdate(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', 'Password berhasil diubah.');
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

        $review = Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'lapangan_id' => $booking->lapangan_id,
            ],
            $data
        );

        // Simpan rata-rata rating agar daftar/API lama tetap kompatibel.
        $averageRating = Review::where('lapangan_id', $booking->lapangan_id)
            ->where('is_visible', true)
            ->avg('rating');

        Lapangan::whereKey($booking->lapangan_id)->update([
            'rating' => round((float) ($averageRating ?? 0), 2),
        ]);

        // Notifikasi ke pemilik venue / superadmin jika ada review baru
        $booking->loadMissing('lapangan.venue');
        $venueOwnerId = $booking->lapangan?->owner_admin_id ?? $booking->lapangan?->venue?->owner_admin_id;
        if ($venueOwnerId) {
            $venueAdmin = User::find($venueOwnerId);
            if ($venueAdmin) {
                $venueAdmin->notify(new ArenaUserNotification(
                    'Ulasan Baru Diterima',
                    $user->name . ' memberikan rating ' . $data['rating'] . '/5 untuk lapangan ' . ($booking->lapangan?->nama ?? 'Lapangan') . ': "' . \Illuminate\Support\Str::limit($data['message'], 60) . '"',
                    route('admin.reviews', [], false)
                ));
            }
        }

        return back()->with(
            'success',
            'Review berhasil dikirim.'
        );
    }
}