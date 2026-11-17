<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PaymentController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);

        $payments = Payment::with(['booking.lapangan.venue', 'extension'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (Payment $payment) => [
                ...$this->paymentPayload($payment),
                'booking' => $payment->booking ? $this->bookingPayload($payment->booking) : null,
                'extension' => $payment->extension ? [
                    'id' => (int) $payment->extension->id,
                    'hours' => (int) $payment->extension->hours,
                    'new_end_time' => substr((string) $payment->extension->new_end_time, 0, 5),
                    'amount' => (int) $payment->extension->amount,
                    'status' => $payment->extension->status,
                ] : null,
            ])->values();

        return $this->ok([
            'payments' => $payments,
            'total' => $payments->count(),
        ]);
    }

    /**
     * Create (or reuse) a Midtrans Snap transaction for a booking.
     */
    public function checkoutBooking(Request $request, Booking $booking)
    {
        abort_unless((int) $booking->user_id === (int) $request->user()->id, 403);

        $payment = Payment::where('booking_id', $booking->id)
            ->whereNull('booking_extension_id')
            ->firstOrFail();

        abort_unless(strtolower((string) $payment->method) === 'midtrans', 409, 'Booking ini tidak menggunakan Midtrans.');
        abort_if($this->paymentIsPaid($payment, $booking), 409, 'Booking ini sudah lunas.');
        abort_if($booking->status === 'Dibatalkan', 409, 'Booking ini sudah dibatalkan.');

        $amount = (int) $booking->total_price;

        abort_if($amount < 1 || (int) $payment->amount !== $amount, 422, 'Nominal pembayaran tidak sesuai dengan total booking.');

        return $this->createSnapTransaction($request->user(), $payment, $amount, 'booking-'.$booking->id, 'Booking ArenaSports', 'ARENA-PAY');
    }

    public function checkoutExtension(Request $request, BookingExtension $extension)
    {
        abort_unless((int) $extension->user_id === (int) $request->user()->id, 403);
        abort_unless($extension->payment_method === 'Midtrans', 409, 'Perpanjangan ini bukan menggunakan Midtrans.');
        abort_unless($extension->status === 'Menunggu', 409, 'Perpanjangan ini tidak lagi menunggu pembayaran.');

        $booking = Booking::findOrFail($extension->booking_id);
        abort_unless($booking->status === 'Lunas', 409, 'Booking awal belum lunas atau sudah tidak aktif.');

        $payment = Payment::where('booking_extension_id', $extension->id)
            ->where('booking_id', $booking->id)
            ->firstOrFail();

        abort_if($this->paymentIsPaid($payment), 409, 'Perpanjangan ini sudah dibayar.');

        $amount = (int) $extension->amount;
        abort_if($amount < 1 || (int) $payment->amount !== $amount, 422, 'Nominal pembayaran perpanjangan tidak sesuai.');

        return $this->createSnapTransaction($request->user(), $payment, $amount, 'extension-'.$extension->id, 'Perpanjangan Booking ArenaSports', 'ARENA-EXT');
    }

    /**
     * Refresh the local payment status from Midtrans.
     */
    public function status(Request $request, Payment $payment)
    {
        abort_unless((int) $payment->user_id === (int) $request->user()->id, 403);

        $payment->load(['booking.lapangan', 'extension']);

        // Cash payments are confirmed by the venue admin, not Midtrans.
        if (strtolower((string) $payment->method) !== 'midtrans') {
            return $this->ok($this->localStatus($payment), 'Status pembayaran dimuat.');
        }

        $serverKey = (string) config('services.midtrans.server_key');
        $orderId = (string) ($payment->midtrans_order_id ?: $payment->transaction_id);

        if ($serverKey === '' || $orderId === '') {
            return $this->ok($this->localStatus($payment), $serverKey === ''
                ? 'Midtrans belum dikonfigurasi. Status lokal ditampilkan.'
                : 'Status pembayaran lokal dimuat.');
        }

        $this->configureMidtrans($serverKey, (bool) config('services.midtrans.is_production'));

        try {
            $remote = \Midtrans\Transaction::status($orderId);
        } catch (Throwable $e) {
            Log::warning('Gagal memeriksa status Midtrans', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return $this->ok($this->localStatus($payment), 'Status transaksi belum dapat diperiksa.');
        }

        $transactionStatus = strtolower((string) ($remote->transaction_status ?? ''));
        $fraudStatus = strtolower((string) ($remote->fraud_status ?? ''));
        $statusCode = (string) ($remote->status_code ?? '');
        $transactionId = (string) ($remote->transaction_id ?? '');
        $grossAmount = (string) ($remote->gross_amount ?? '');

        $isPaid = ($transactionStatus === 'settlement' && $statusCode === '200')
            || ($transactionStatus === 'capture' && $statusCode === '200'
                && in_array($fraudStatus, ['', 'accept'], true));

        if ($isPaid) {
            if ($payment->booking_extension_id) {
                $this->markExtensionPaid($payment, $orderId, $transactionId, $grossAmount);
            } else {
                $this->markBookingPaid($payment, $orderId, $transactionId, $grossAmount);
            }
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true)) {
            $this->markFailed($payment, $orderId, $transactionId, $transactionStatus);
        }

        $payment->refresh();

        return $this->ok([
            ...$this->localStatus($payment),
            'remote' => [
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'status_code' => $statusCode,
            ],
        ], 'Status pembayaran diperbarui.');
    }

    private function createSnapTransaction(
        User $user,
        Payment $payment,
        int $amount,
        string $itemId,
        string $itemName,
        string $prefix
    ) {
        $serverKey = (string) config('services.midtrans.server_key');
        $clientKey = (string) config('services.midtrans.client_key');
        $isProduction = (bool) config('services.midtrans.is_production');

        if ($serverKey === '') {
            return $this->fail(
                'Midtrans belum dikonfigurasi di server. Isi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY pada .env.',
                503
            );
        }

        $this->configureMidtrans($serverKey, $isProduction);

        if ($payment->snap_token && ($payment->midtrans_order_id || $payment->transaction_id)) {
            $host = $isProduction
                ? 'https://app.midtrans.com/snap/v2/vtweb/'
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/';

            return $this->ok([
                'payment_id' => (int) $payment->id,
                'order_id' => $payment->midtrans_order_id,
                'snap_token' => $payment->snap_token,
                'redirect_url' => $host.$payment->snap_token,
                'client_key' => $clientKey,
                'is_production' => $isProduction,
            ], 'Lanjutkan pembayaran.');
        }

        $orderId = $prefix.'-'.$payment->id.'-TRY-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(3));

        try {
            $snap = \Midtrans\Snap::createTransaction([
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'customer_details' => [
                    'first_name' => $user->name ?: 'ArenaSports User',
                    'email' => $user->email ?: '',
                    'phone' => $user->phone ?: '',
                ],
                'item_details' => [[
                    'id' => $itemId,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => $itemName,
                ]],
            ]);

            if (empty($snap->token) || empty($snap->redirect_url)) {
                throw new RuntimeException('Midtrans tidak mengembalikan Snap token atau URL.');
            }

            DB::transaction(function () use ($payment, $amount, $orderId, $snap) {
                $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

                if ($locked->status === 'Lunas') {
                    return;
                }

                $locked->method = 'Midtrans';
                $locked->amount = $amount;
                $locked->status = 'Menunggu';
                $locked->midtrans_order_id = $orderId;
                $locked->snap_token = $snap->token;
                $locked->save();
            }, 3);

            return $this->ok([
                'payment_id' => (int) $payment->id,
                'order_id' => $orderId,
                'snap_token' => $snap->token,
                'redirect_url' => $snap->redirect_url,
                'client_key' => $clientKey,
                'is_production' => $isProduction,
            ], 'Transaksi pembayaran berhasil dibuat.');
        } catch (Throwable $e) {
            Log::error('Midtrans mobile checkout gagal', [
                'payment_id' => $payment->id,
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return $this->fail('Gagal membuat pembayaran Midtrans. Periksa konfigurasi dan log server.', 502);
        }
    }

    private function localStatus(Payment $payment): array
    {
        $booking = $payment->booking;

        return [
            'payment' => $this->paymentPayload($payment),
            'booking' => $booking ? $this->bookingPayload($booking) : null,
            'is_paid' => $this->paymentIsPaid($payment, $booking),
        ];
    }

    private function markBookingPaid(Payment $payment, string $orderId, string $transactionId, string $grossAmount): void
    {
        DB::transaction(function () use ($payment, $orderId, $transactionId, $grossAmount) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->booking_extension_id) {
                return;
            }

            $booking = Booking::whereKey($locked->booking_id)->lockForUpdate()->firstOrFail();

            if ($this->toMinorUnits($grossAmount) !== $this->toMinorUnits((string) $booking->total_price)
                || $this->toMinorUnits((string) $locked->amount) !== $this->toMinorUnits((string) $booking->total_price)) {
                throw new RuntimeException('Nominal Midtrans tidak cocok dengan total booking.');
            }

            $user = User::whereKey($booking->user_id)->lockForUpdate()->firstOrFail();
            $alreadyPaid = $locked->status === 'Lunas' && $booking->status === 'Lunas';

            if (! $locked->reward_points_awarded) {
                $user->increment('points', 10);
                $locked->reward_points_awarded = true;
            }

            if ($alreadyPaid) {
                $locked->save();

                return;
            }

            $locked->midtrans_order_id = $orderId;
            $locked->transaction_id = $transactionId;
            $locked->status = 'Lunas';
            $locked->paid_at = $locked->paid_at ?? now();
            $locked->save();

            $booking->status = 'Lunas';
            $booking->save();

            $user->notify(new ArenaUserNotification(
                'Pembayaran Berhasil',
                'Pembayaran booking #'.$booking->id.' berhasil. Anda mendapatkan 10 poin.',
                route('user.bookings', [], false)
            ));
        }, 3);
    }

    private function markExtensionPaid(Payment $payment, string $orderId, string $transactionId, string $grossAmount): void
    {
        DB::transaction(function () use ($payment, $orderId, $transactionId, $grossAmount) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->booking_extension_id) {
                return;
            }

            $extension = BookingExtension::whereKey($locked->booking_extension_id)->lockForUpdate()->firstOrFail();
            $booking = Booking::whereKey($extension->booking_id)->lockForUpdate()->firstOrFail();

            if ($this->toMinorUnits($grossAmount) !== $this->toMinorUnits((string) $locked->amount)
                || $this->toMinorUnits((string) $locked->amount) !== $this->toMinorUnits((string) $extension->amount)) {
                throw new RuntimeException('Nominal pembayaran perpanjangan tidak cocok.');
            }

            if ($extension->status === 'Lunas' && $extension->processed_at !== null) {
                return;
            }

            if ($extension->status !== 'Menunggu' || $booking->status !== 'Lunas') {
                return;
            }

            if (substr((string) $booking->end_time, 0, 8) !== substr((string) $extension->previous_end_time, 0, 8)) {
                throw new RuntimeException('Waktu akhir booking sudah berubah.');
            }

            $booking->end_time = $extension->new_end_time;
            $booking->hours = (int) $booking->hours + (int) $extension->hours;
            $booking->total_price = (float) $booking->total_price + (float) $extension->amount;
            $booking->save();

            $extension->status = 'Lunas';
            $extension->processed_at = now();
            $extension->save();

            $locked->midtrans_order_id = $orderId;
            $locked->transaction_id = $transactionId;
            $locked->status = 'Lunas';
            $locked->paid_at = $locked->paid_at ?? now();

            if (! $locked->reward_points_awarded && (float) $extension->amount > 0) {
                $user = User::whereKey($booking->user_id)->lockForUpdate()->first();
                if ($user) {
                    $user->increment('points', 10);
                }
                $locked->reward_points_awarded = true;
            }

            $locked->save();

            $user = User::find($booking->user_id);

            if ($user) {
                $user->notify(new ArenaUserNotification(
                    'Perpanjangan Booking Berhasil',
                    'Perpanjangan booking #'.$booking->id.' berhasil dibayar. Jam selesai sekarang '
                        .substr((string) $extension->new_end_time, 0, 5).'.',
                    route('user.bookings', [], false)
                ));
            }
        }, 3);
    }

    private function markFailed(Payment $payment, string $orderId, string $transactionId, string $transactionStatus): void
    {
        DB::transaction(function () use ($payment, $orderId, $transactionId, $transactionStatus) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $locked->midtrans_order_id = $orderId;
            $locked->transaction_id = $transactionId;

            if ($locked->booking_extension_id) {
                $extension = BookingExtension::whereKey($locked->booking_extension_id)->lockForUpdate()->firstOrFail();

                if ($extension->status === 'Lunas' || $extension->processed_at !== null) {
                    return;
                }

                $locked->status = 'Gagal';
                $locked->save();

                $extension->status = 'Gagal';
                $extension->save();

                $user = User::find($extension->user_id);

                if ($user) {
                    $user->notify(new ArenaUserNotification(
                        'Pembayaran Perpanjangan Gagal',
                        'Pembayaran perpanjangan booking #'.$extension->booking_id
                            .' gagal atau kedaluwarsa. Booking awal tetap berlaku.',
                        route('user.bookings', [], false)
                    ));
                }

                return;
            }

            $booking = Booking::whereKey($locked->booking_id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'Lunas' || $booking->status === 'Lunas') {
                return;
            }

            if ($locked->status === 'Gagal' && $booking->status === 'Dibatalkan') {
                return;
            }

            $locked->status = 'Gagal';
            $locked->save();

            $booking->status = 'Dibatalkan';
            $booking->save();

            $user = User::find($booking->user_id);

            if ($user) {
                $label = $transactionStatus === 'expire'
                    ? 'Kedaluwarsa'
                    : ($transactionStatus === 'cancel' ? 'Dibatalkan' : 'Gagal');

                $user->notify(new ArenaUserNotification(
                    'Pembayaran '.$label,
                    'Pembayaran booking #'.$booking->id.' '.strtolower($label).'.',
                    route('user.payments', [], false)
                ));
            }
        }, 3);
    }

    private function configureMidtrans(string $serverKey, bool $isProduction): void
    {
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = $isProduction;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    private function toMinorUnits(string $amount): int
    {
        if (! is_numeric($amount)) {
            throw new RuntimeException('Nominal Midtrans tidak valid.');
        }

        return (int) round(((float) $amount) * 100);
    }
}
