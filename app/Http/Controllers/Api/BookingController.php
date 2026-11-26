<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Lapangan;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);

        $query = Booking::with(['lapangan.venue', 'payment', 'payments', 'extensions.payment', 'openMatch'])
            ->where('user_id', $user->id);

        $scope = (string) $request->query('scope', 'all');

        if ($scope === 'active') {
            $query->whereIn('status', ['Menunggu', 'DP', 'Lunas', 'Selesai']);
        } elseif ($scope === 'completed') {
            $query->whereIn('status', ['Selesai', 'Dibatalkan']);
        }

        $bookings = $query->latest()->get()
            ->map(fn (Booking $booking) => $this->bookingPayload($booking, true))
            ->values();

        return $this->ok([
            'bookings' => $bookings,
            'total' => $bookings->count(),
        ]);
    }

    public function eligible(Request $request)
    {
        $user = $this->currentUser($request);

        $bookings = Booking::with(['lapangan.venue', 'payment'])
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

        return $this->ok(['bookings' => $bookings]);
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);

        return $this->ok([
            'booking' => $this->bookingPayload($booking, true),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->currentUser($request);

        $data = $request->validate([
            'lapangan_id' => ['required', 'integer', 'exists:lapangan,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'hours' => ['required', 'integer', 'min:1', 'max:8'],
            'payment_method' => ['required', Rule::in(['Midtrans', 'Cash'])],
        ]);

        /** @var Lapangan $lapangan */
        $lapangan = Lapangan::with('venue')->findOrFail($data['lapangan_id']);

        abort_unless(
            $lapangan->is_bookable,
            422,
            'Lapangan tidak tersedia atau venue belum aktif.'
        );

        if (
            $data['booking_date'] === today()->toDateString()
            && strtotime($data['start_time']) <= strtotime(now()->format('H:i'))
        ) {
            throw ValidationException::withMessages([
                'start_time' => 'Untuk booking hari ini, jam mulai harus masih akan datang.',
            ]);
        }

        $endTime = date('H:i', strtotime($data['start_time']) + ((int) $data['hours'] * 3600));

        if ($endTime <= $data['start_time']) {
            throw ValidationException::withMessages([
                'hours' => 'Jam selesai melewati tengah malam. Pilih durasi yang lebih pendek.',
            ]);
        }

        $pricing = app(\App\Services\CourtPricingService::class);

        if (!$pricing->isOperatingDay($lapangan->venue, $data['booking_date'])) {
            throw ValidationException::withMessages([
                'booking_date' => 'Venue tidak beroperasi pada hari tersebut.',
            ]);
        }

        if (!$pricing->withinOperatingHours($lapangan->venue, $data['start_time'], (int) $data['hours'])) {
            throw ValidationException::withMessages([
                'start_time' => 'Jam booking di luar jam operasional venue.',
            ]);
        }

        if ($this->hasOverlap($lapangan->id, $data['booking_date'], $data['start_time'], $endTime)) {
            throw ValidationException::withMessages([
                'start_time' => 'Jadwal tersebut sudah dipesan atau sedang menunggu pembayaran.',
            ]);
        }

        $booking = DB::transaction(function () use ($user, $lapangan, $data, $endTime) {
            Lapangan::whereKey($lapangan->id)->lockForUpdate()->firstOrFail();

            if ($this->hasOverlap($lapangan->id, $data['booking_date'], $data['start_time'], $endTime)) {
                throw ValidationException::withMessages([
                    'start_time' => 'Jadwal tersebut baru saja dipesan pengguna lain. Pilih jam lain.',
                ]);
            }

            $totalPrice = app(\App\Services\CourtPricingService::class)
                ->quote($lapangan, $data['booking_date'], $data['start_time'], (int) $data['hours'])['total'];

            $booking = Booking::create([
                'user_id' => $user->id,
                'lapangan_id' => $lapangan->id,
                'payment_method' => $data['payment_method'],
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
                'method' => $data['payment_method'],
                'amount' => $totalPrice,
                'status' => 'Menunggu',
            ]);

            $user->notify(new ArenaUserNotification(
                'Booking Berhasil Dibuat',
                'Booking lapangan '.($lapangan->nama ?? '').' untuk '.$data['booking_date']
                    .' berhasil dibuat dan menunggu pembayaran/konfirmasi.',
                route('user.payments', [], false)
            ));

            $venueOwner = $lapangan->venue?->owner ?? User::find($lapangan->owner_admin_id);

            if ($venueOwner && $venueOwner->role === 'venue_admin') {
                $venueOwner->notify(new ArenaUserNotification(
                    'Booking Baru Masuk',
                    'Ada booking baru untuk lapangan '.($lapangan->nama ?? '-').' pada '.$data['booking_date'].'.',
                    route('admin.bookings', [], false)
                ));
            }

            return $booking;
        });

        $booking->load(['lapangan.venue', 'payment']);

        return $this->ok([
            'booking' => $this->bookingPayload($booking),
            'requires_payment' => $data['payment_method'] === 'Midtrans',
        ], $data['payment_method'] === 'Cash'
            ? 'Booking berhasil dibuat. Pembayaran tunai dilakukan di venue dan harus dikonfirmasi Admin Venue.'
            : 'Booking berhasil dibuat. Lanjutkan pembayaran melalui Midtrans.', 201);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);

        // Mirror the effective status logic from BaseApiController::bookingPayload.
        $effectiveStatus = $booking->status;
        if (! in_array($effectiveStatus, ['Dibatalkan', 'Selesai'], true)) {
            $end = Carbon::parse(
                $booking->booking_date?->toDateString().' '.substr((string) $booking->end_time, 0, 5)
            );
            if ($end->isPast()) {
                $effectiveStatus = 'Selesai';
            }
        }

        if (! in_array($effectiveStatus, ['Menunggu', 'DP'], true)) {
            return $this->fail('Booking ini tidak bisa dibatalkan pada saat ini.', 422);
        }

        $booking->update(['status' => 'Dibatalkan']);

        return $this->ok([
            'booking' => $this->bookingPayload($booking, true),
        ], 'Booking berhasil dibatalkan.');
    }

    public function ticket(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);

        $booking->load(['lapangan.venue', 'payment', 'user']);

        if (! $this->paymentIsPaid($booking->payment, $booking)) {
            return $this->fail('Tiket hanya tersedia setelah pembayaran lunas.', 403);
        }

        return $this->ok([
            'booking' => $this->bookingPayload($booking),
            'ticket_code' => $this->ticketCodeFor($booking),
            'qr_payload' => $this->ticketCodeFor($booking),
        ]);
    }

    public function review(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);

        if (! in_array($booking->status, ['Lunas', 'Selesai'], true)) {
            return $this->fail('Review hanya dapat diberikan untuk booking yang selesai.', 403);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'message' => ['required', 'string', 'max:1500'],
        ]);

        Review::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'lapangan_id' => $booking->lapangan_id,
            ],
            $data
        );

        return $this->ok(null, 'Review berhasil dikirim.');
    }

    public function extension(Request $request, Booking $booking)
    {
        $user = $this->currentUser($request);

        $this->authorizeOwner($request, $booking);

        $data = $request->validate([
            'hours' => ['required', 'integer', 'min:1', 'max:8'],
        ]);

        $extension = DB::transaction(function () use ($booking, $user, $data) {
            $lapangan = Lapangan::whereKey($booking->lapangan_id)->lockForUpdate()->firstOrFail();
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'Lunas') {
                throw ValidationException::withMessages([
                    'hours' => 'Perpanjangan hanya tersedia untuk booking yang sudah lunas.',
                ]);
            }

            $originalPayment = Payment::where('booking_id', $locked->id)
                ->whereNull('booking_extension_id')
                ->whereIn(DB::raw('LOWER(status)'), ['lunas', 'selesai', 'settlement', 'capture', 'success'])
                ->exists();

            if (! $originalPayment) {
                throw ValidationException::withMessages([
                    'hours' => 'Pembayaran booking awal belum terverifikasi lunas.',
                ]);
            }

            if (BookingExtension::where('booking_id', $locked->id)->where('status', 'Menunggu')->exists()) {
                throw ValidationException::withMessages([
                    'hours' => 'Masih ada perpanjangan yang menunggu pembayaran.',
                ]);
            }

            $previousEnd = substr((string) $locked->end_time, 0, 5);
            $newEnd = date('H:i', strtotime($previousEnd) + ((int) $data['hours'] * 3600));

            if ($newEnd <= $previousEnd) {
                throw ValidationException::withMessages([
                    'hours' => 'Perpanjangan melewati tengah malam. Pilih durasi yang lebih pendek.',
                ]);
            }

            $bookingDate = $locked->booking_date->toDateString();

            $overlap = Booking::where('lapangan_id', $locked->lapangan_id)
                ->whereDate('booking_date', $bookingDate)
                ->where('id', '!=', $locked->id)
                ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
                ->where('start_time', '<', $newEnd)
                ->where('end_time', '>', $previousEnd)
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'hours' => 'Jadwal perpanjangan berbenturan dengan booking lain.',
                ]);
            }

            $amount = app(\App\Services\CourtPricingService::class)
                ->quote($lapangan, $bookingDate, $previousEnd, (int) $data['hours'])['total'];

            $extension = BookingExtension::create([
                'booking_id' => $locked->id,
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
                'booking_id' => $locked->id,
                'booking_extension_id' => $extension->id,
                'user_id' => $user->id,
                'method' => 'Midtrans',
                'amount' => $amount,
                'status' => 'Menunggu',
            ]);

            return $extension;
        });

        return $this->ok([
            'extension' => [
                'id' => (int) $extension->id,
                'booking_id' => (int) $extension->booking_id,
                'hours' => (int) $extension->hours,
                'amount' => (int) $extension->amount,
                'status' => $extension->status,
            ],
            'requires_payment' => true,
        ], 'Pengajuan perpanjangan berhasil dibuat. Silakan lanjutkan pembayaran.', 201);
    }

    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless(
            (int) $booking->user_id === (int) $request->user()->id,
            403,
            'Booking ini bukan milik akun kamu.'
        );
    }

    private function hasOverlap(int $lapanganId, string $date, string $startTime, string $endTime): bool
    {
        $overlap = Booking::where('lapangan_id', $lapanganId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['Menunggu', 'DP', 'Lunas'])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        if ($overlap) {
            return true;
        }

        return BookingExtension::where('status', 'Menunggu')
            ->where('previous_end_time', '<', $endTime)
            ->where('new_end_time', '>', $startTime)
            ->whereHas('booking', function ($query) use ($lapanganId, $date) {
                $query->where('lapangan_id', $lapanganId)
                    ->whereDate('booking_date', $date)
                    ->whereIn('status', ['Menunggu', 'DP', 'Lunas']);
            })
            ->exists();
    }
}
