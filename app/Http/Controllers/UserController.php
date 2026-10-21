<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Lapangan;
use App\Models\OpenMatch;
use App\Models\Payment;
use App\Models\Redemption;
use App\Models\Review;
use App\Models\Reward;
use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class UserController extends Controller
{
    private function currentUser(Request $request): User
    {
        return User::findOrFail(
            $request->session()->get('admin_user_id')
        );
    }

    public function home(Request $request): View
    {
        $user = $this->currentUser($request);

        $courts = Lapangan::where('status', 'Tersedia')
            ->latest()
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
            'bookings',
            'matches'
        ));
    }

    public function courts(Request $request): View
    {
        $user = $this->currentUser($request);

        $query = Lapangan::query()
            ->where('status', 'Tersedia');

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->q . '%')
                    ->orWhere('lokasi', 'like', '%' . $request->q . '%')
                    ->orWhere('jenis', 'like', '%' . $request->q . '%');
            });
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $courts = $query->latest()->get();

        if (
            $request->filled('lat') &&
            $request->filled('lng') &&
            is_numeric($request->lat) &&
            is_numeric($request->lng)
        ) {
            $lat = (float) $request->lat;
            $lng = (float) $request->lng;

            $courts = $courts->map(function ($court) use ($lat, $lng) {
                $court->distance_km = null;

                if (
                    $court->latitude !== null &&
                    $court->longitude !== null
                ) {
                    $dLat = deg2rad((float) $court->latitude - $lat);
                    $dLng = deg2rad((float) $court->longitude - $lng);

                    $a = sin($dLat / 2) ** 2
                        + cos(deg2rad($lat))
                        * cos(deg2rad((float) $court->latitude))
                        * sin($dLng / 2) ** 2;

                    $court->distance_km = 6371 * 2 * atan2(
                        sqrt($a),
                        sqrt(max(0, 1 - $a))
                    );
                }

                return $court;
            })->sortBy(
                fn ($court) => $court->distance_km ?? INF
            )->values();
        }

        return view('user.courts', compact('user', 'courts'));
    }

    public function courtDetail(
        Request $request,
        Lapangan $lapangan
    ): View {
        $user = $this->currentUser($request);

        abort_unless($lapangan->status === 'Tersedia', 404);

        return view('user.court-detail', compact('user', 'lapangan'));
    }

    public function bookingStore(
        Request $request,
        Lapangan $lapangan
    ): RedirectResponse {
        $user = $this->currentUser($request);

        abort_unless(
            $lapangan->status === 'Tersedia',
            422,
            'Lapangan tidak tersedia.'
        );

        $data = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'hours' => ['required', 'integer', 'min:1', 'max:8'],
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

        if ($overlap) {
            return back()->withErrors([
                'start_time' => 'Jadwal tersebut sudah dipesan. Silakan pilih jam lain.',
            ])->withInput();
        }

        
$booking = DB::transaction(function () use ($user, $lapangan, $data, $endTime) {
    $totalPrice = (int) $lapangan->harga * (int) $data['hours'];
    $booking = Booking::create([
        'user_id' => $user->id,
        'lapangan_id' => $lapangan->id,
        'payment_method' => 'Midtrans',
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
        'method' => 'Midtrans',
        'amount' => $totalPrice,
        'status' => 'Menunggu',
    ]);

    return $booking;
});


        return redirect()->route('user.midtrans.start', ['booking' => $booking->id])
            ->with('success', 'Booking berhasil dibuat. Lanjutkan pembayaran penuh melalui Midtrans.');
    }

    public function bookings(Request $request): View
    {
        $user = $this->currentUser($request);

        // Booking Saya hanya menampilkan booking yang pembayarannya sudah berhasil.
        $bookings = Booking::with(['lapangan', 'payment', 'user'])
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereIn('status', ['Lunas', 'Selesai'])
                    ->orWhereHas('payment', function ($paymentQuery) {
                        $paymentQuery->whereIn(DB::raw('LOWER(status)'), [
                            'lunas', 'selesai', 'settlement', 'capture', 'success',
                        ]);
                    });
            })
            ->latest()
            ->get();

        return view('user.bookings', compact('user', 'bookings'));
    }

    public function payments(Request $request): View
    {
        $user = $this->currentUser($request);

        $payments = Payment::with('booking.lapangan')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('user.payments', compact('user', 'payments'));
    }

    public function bookingTicket(Request $request, Booking $booking): View
    {
        $user = $this->currentUser($request);
        abort_unless((int) $booking->user_id === (int) $user->id, 403);

        $booking->load(['lapangan', 'payment', 'user']);
        $paymentStatus = strtolower((string) ($booking->payment?->status ?? $booking->status));
        abort_unless(in_array($paymentStatus, ['lunas', 'selesai', 'settlement', 'capture', 'success'], true), 403, 'Tiket hanya tersedia setelah pembayaran lunas.');

        // Kode tiket stabil dan tidak mengekspos ID database sebagai satu-satunya rahasia.
        $secret = (string) config('app.key');
        $ticketCode = 'AS-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT)
            . '-' . strtoupper(substr(hash_hmac('sha256', 'booking:' . $booking->id, $secret), 0, 10));

        return view('user.booking-ticket', compact('user', 'booking', 'ticketCode'));
    }

    public function paymentProof(
        Request $request,
        Payment $payment
    ): RedirectResponse {
        $user = $this->currentUser($request);
        abort_unless((int) $payment->user_id === (int) $user->id, 403);

        return back()->withErrors([
            'payment' => 'Pembayaran ArenaSports diproses otomatis melalui Midtrans. Unggah bukti manual tidak digunakan.',
        ]);
    }

    public function openMatches(Request $request): View
    {
        $user = $this->currentUser($request);

        // Open Match yang dibuat pengguna.
        $matches = OpenMatch::with('booking.lapangan')
            ->whereIn('status', ['Open', 'Penuh'])
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->get();

        // Event resmi yang diterbitkan Admin/Superadmin.
        $events = Event::where('status', 'Publikasi')
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->get();

        // Booking aktif yang belum digunakan untuk Open Match.
        $eligibleBookings = Booking::with('lapangan')
            ->where('user_id', $user->id)
            ->whereIn('status', ['DP', 'Lunas'])
            ->whereDate('booking_date', '>=', today())
            ->whereDoesntHave('openMatch')
            ->orderBy('booking_date')
            ->get();

        return view('user.open-matches', compact(
            'user',
            'matches',
            'events',
            'eligibleBookings'
        ));
    }

    public function openMatchStore(Request $request): RedirectResponse
    {
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
            !$booking ||
            !Carbon::parse(
                $booking->booking_date->toDateString() . ' ' . $booking->start_time
            )->isFuture()
        ) {
            return back()->withErrors([
                'booking_id' => 'Open Match hanya dapat dibuat dari booking aktif milik sendiri sebelum jam bermain dimulai.',
            ]);
        }

        $existingMatch = OpenMatch::where('booking_id', $booking->id)
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

        return back()->with('success', 'Open Match berhasil dibuat.');
    }

    public function joinMatch(
        Request $request,
        OpenMatch $openMatch
    ): RedirectResponse {
        $user = $this->currentUser($request);

        $matchStart = $openMatch->tanggal && $openMatch->start_time
            ? Carbon::parse(
                $openMatch->tanggal->toDateString() . ' ' . $openMatch->start_time
            )
            : null;

        abort_unless(
            $openMatch->status === 'Open' &&
            $matchStart &&
            $matchStart->isFuture(),
            422,
            'Open Match sudah tidak tersedia.'
        );

        $result = DB::transaction(function () use ($openMatch, $user) {
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

        return back()->with('success', 'Berhasil bergabung ke Open Match.');
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

            $lockedUser->decrement('points', $reward->points_required);

            Redemption::create([
                'user_id' => $lockedUser->id,
                'reward_id' => $reward->id,
                'redeemed_at' => now(),
                'qr_code' => 'AR-' . $lockedUser->id
                    . '-' . $reward->id
                    . '-' . Str::upper(Str::random(6)),
                'status' => 'Berhasil',
            ]);
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

    public function profileUpdate(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function reviewStore(
        Request $request,
        Booking $booking
    ): RedirectResponse {
        $user = $this->currentUser($request);

        abort_unless(
            $booking->user_id === $user->id &&
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

        return back()->with('success', 'Review berhasil dikirim.');
    }
}
