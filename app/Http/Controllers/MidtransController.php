<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingExtension;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MidtransController extends Controller
{
    /**
     * CHECKOUT BOOKING AWAL
     */
    public function checkout(
        Request $request,
        Booking $booking
    ): RedirectResponse {
        $userId = (int) $request->session()->get('admin_user_id');

        abort_unless(
            $userId > 0 && (int) $booking->user_id === $userId,
            403
        );

        $serverKey = (string) config('services.midtrans.server_key');
        $clientKey = (string) config('services.midtrans.client_key');
        $isProduction = (bool) config('services.midtrans.is_production');

        abort_unless(
            $serverKey !== '' && $clientKey !== '',
            503,
            'Midtrans belum dikonfigurasi.'
        );

        // Ambil pembayaran booking awal saja.
        $payment = Payment::where('booking_id', $booking->id)
            ->whereNull('booking_extension_id')
            ->firstOrFail();

        abort_unless(
            strtolower((string) $payment->method) === 'midtrans',
            409,
            'Booking ini tidak menggunakan Midtrans.'
        );

        abort_if(
            $payment->status === 'Lunas'
                || $booking->status === 'Lunas',
            409,
            'Booking ini sudah lunas.'
        );

        abort_if(
            $booking->status === 'Dibatalkan',
            409,
            'Booking ini sudah dibatalkan.'
        );

        $amount = (int) $booking->total_price;

        abort_if(
            $amount < 1 || (int) $payment->amount !== $amount,
            422,
            'Nominal pembayaran tidak sesuai dengan total booking.'
        );

        $this->configureMidtrans($serverKey, $isProduction);

        if (
            $payment->snap_token
            && ($payment->midtrans_order_id || $payment->transaction_id)
        ) {
            $host = $isProduction
                ? 'https://app.midtrans.com/snap/v2/vtweb/'
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/';

            return redirect()->away($host . $payment->snap_token);
        }

        $customer = User::find($userId);

        $orderId = 'ARENA-PAY-' . $payment->id
            . '-TRY-' . now()->format('YmdHis')
            . '-' . bin2hex(random_bytes(3));

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $customer?->name ?? 'ArenaSports User',
                'email' => $customer?->email ?? '',
            ],
            'item_details' => [[
                'id' => 'booking-' . $booking->id,
                'price' => $amount,
                'quantity' => 1,
                'name' => 'Booking ArenaSports',
            ]],
        ];

        try {
            $snap = \Midtrans\Snap::createTransaction($params);

            if (empty($snap->token) || empty($snap->redirect_url)) {
                throw new RuntimeException(
                    'Midtrans tidak mengembalikan Snap token atau URL.'
                );
            }

            DB::transaction(function () use (
                $payment,
                $amount,
                $orderId,
                $snap
            ): void {
                $locked = Payment::whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

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

            $payment->refresh();

            if (
                $payment->midtrans_order_id !== $orderId
                || $payment->status === 'Lunas'
            ) {
                return back()->with(
                    'error',
                    'Data pembayaran berubah. Periksa status transaksi.'
                );
            }

            return redirect()->away($snap->redirect_url);
        } catch (Throwable $e) {
            Log::error('Midtrans checkout booking error', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Gagal membuat pembayaran. Periksa log aplikasi.'
            );
        }
    }

    /**
     * CHECKOUT PERPANJANGAN BOOKING
     */
    public function extensionCheckout(
        Request $request,
        BookingExtension $extension
    ): RedirectResponse {
        $userId = (int) $request->session()->get('admin_user_id');

        abort_unless(
            $userId > 0 && (int) $extension->user_id === $userId,
            403
        );

        abort_unless(
            $extension->payment_method === 'Midtrans',
            409,
            'Perpanjangan ini bukan menggunakan Midtrans.'
        );

        abort_unless(
            $extension->status === 'Menunggu',
            409,
            'Perpanjangan ini tidak lagi menunggu pembayaran.'
        );

        $booking = Booking::findOrFail($extension->booking_id);

        abort_unless(
            $booking->status === 'Lunas',
            409,
            'Booking awal belum lunas atau sudah tidak aktif.'
        );

        $payment = Payment::where('booking_extension_id', $extension->id)
            ->where('booking_id', $booking->id)
            ->firstOrFail();

        abort_if(
            $payment->status === 'Lunas',
            409,
            'Perpanjangan ini sudah dibayar.'
        );

        $serverKey = (string) config('services.midtrans.server_key');
        $clientKey = (string) config('services.midtrans.client_key');
        $isProduction = (bool) config('services.midtrans.is_production');

        abort_unless(
            $serverKey !== '' && $clientKey !== '',
            503,
            'Midtrans belum dikonfigurasi.'
        );

        $amount = (int) $extension->amount;

        abort_if(
            $amount < 1
                || (int) $payment->amount !== $amount,
            422,
            'Nominal pembayaran perpanjangan tidak sesuai.'
        );

        $this->configureMidtrans($serverKey, $isProduction);

        if (
            $payment->snap_token
            && ($payment->midtrans_order_id || $payment->transaction_id)
        ) {
            $host = $isProduction
                ? 'https://app.midtrans.com/snap/v2/vtweb/'
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/';

            return redirect()->away($host . $payment->snap_token);
        }

        $customer = User::find($userId);

        $orderId = 'ARENA-EXT-' . $payment->id
            . '-TRY-' . now()->format('YmdHis')
            . '-' . bin2hex(random_bytes(3));

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $customer?->name ?? 'ArenaSports User',
                'email' => $customer?->email ?? '',
            ],
            'item_details' => [[
                'id' => 'extension-' . $extension->id,
                'price' => $amount,
                'quantity' => 1,
                'name' => 'Perpanjangan Booking ArenaSports',
            ]],
        ];

        try {
            $snap = \Midtrans\Snap::createTransaction($params);

            if (empty($snap->token) || empty($snap->redirect_url)) {
                throw new RuntimeException(
                    'Midtrans tidak mengembalikan Snap token atau URL.'
                );
            }

            DB::transaction(function () use (
                $payment,
                $amount,
                $orderId,
                $snap
            ): void {
                $locked = Payment::whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

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

            $payment->refresh();

            if (
                $payment->midtrans_order_id !== $orderId
                || $payment->status === 'Lunas'
            ) {
                return back()->with(
                    'error',
                    'Data pembayaran perpanjangan berubah. Periksa status transaksi.'
                );
            }

            return redirect()->away($snap->redirect_url);
        } catch (Throwable $e) {
            Log::error('Midtrans extension checkout error', [
                'extension_id' => $extension->id,
                'payment_id' => $payment->id,
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Gagal membuat pembayaran perpanjangan.'
            );
        }
    }

    /**
     * NOTIFIKASI MIDTRANS
     */
    public function notification(Request $request): JsonResponse
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            Log::error('Midtrans Server Key belum dikonfigurasi.');

            return response()->json(['ok' => false], 500);
        }

        $payload = $request->all();

        $orderId = (string) ($payload['order_id'] ?? '');
        $midtransTransactionId = (string) (
            $payload['transaction_id'] ?? ''
        );
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) (
            $payload['transaction_status'] ?? ''
        );
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        if (
            $orderId === ''
            || $midtransTransactionId === ''
            || $statusCode === ''
            || $grossAmount === ''
            || $signature === ''
            || $transactionStatus === ''
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'Payload notifikasi tidak lengkap.',
            ], 400);
        }

        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $serverKey
        );

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Signature notifikasi Midtrans tidak valid.', [
                'order_id' => $orderId,
            ]);

            return response()->json(['ok' => false], 403);
        }

        if (!$this->findPaymentByOrderId($orderId)->exists()) {
            Log::warning('Order ID Midtrans tidak ditemukan.', [
                'order_id' => $orderId,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Pembayaran lokal tidak ditemukan.',
            ], 500);
        }

        try {
            $this->configureMidtrans(
                $serverKey,
                (bool) config('services.midtrans.is_production')
            );

            $verified = \Midtrans\Transaction::status(
                $midtransTransactionId
            );

            $verifiedOrderId = (string) ($verified->order_id ?? '');
            $verifiedTransactionId = (string) (
                $verified->transaction_id ?? ''
            );
            $verifiedStatusCode = (string) (
                $verified->status_code ?? ''
            );
            $verifiedGrossAmount = (string) (
                $verified->gross_amount ?? ''
            );
            $verifiedStatus = strtolower(
                (string) ($verified->transaction_status ?? '')
            );
            $verifiedFraud = strtolower(
                (string) ($verified->fraud_status ?? '')
            );
            $verifiedPaymentType = strtolower(
                (string) ($verified->payment_type ?? '')
            );

            if (
                $verifiedTransactionId === ''
                || $verifiedTransactionId !== $midtransTransactionId
            ) {
                throw new RuntimeException(
                    'Transaction ID dari API Midtrans tidak cocok.'
                );
            }

            $isDanaOrderIdFallback =
                $verifiedPaymentType === 'dana'
                && $verifiedOrderId === $verifiedTransactionId
                && $verifiedTransactionId === $midtransTransactionId;

            if (
                $verifiedOrderId !== $orderId
                && !$isDanaOrderIdFallback
            ) {
                throw new RuntimeException(
                    'Order ID dari API Midtrans tidak cocok.'
                );
            }

            if ($verifiedStatusCode !== $statusCode) {
                throw new RuntimeException(
                    'Status code notifikasi tidak cocok.'
                );
            }

            if (
                $this->toMinorUnits($verifiedGrossAmount)
                !== $this->toMinorUnits($grossAmount)
            ) {
                throw new RuntimeException(
                    'Nominal notifikasi tidak cocok dengan API Midtrans.'
                );
            }

            $transactionStatus = $verifiedStatus;
            $fraudStatus = $verifiedFraud;
            $statusCode = $verifiedStatusCode;
            $grossAmount = $verifiedGrossAmount;

            // Ambil tipe transaksi dari database.
            $payment = $this->findPaymentByOrderId($orderId)
                ->firstOrFail();

            if ($this->isSuccessfulStatus(
                $statusCode,
                $transactionStatus,
                $fraudStatus
            )) {
                if ($payment->booking_extension_id) {
                    $this->markExtensionPaymentAsPaid(
                        $orderId,
                        $midtransTransactionId,
                        $grossAmount
                    );
                } else {
                    $this->markPaymentAsPaid(
                        $orderId,
                        $midtransTransactionId,
                        $grossAmount
                    );
                }
            } else {
                $this->handleUnsuccessfulPayment(
                    $orderId,
                    $midtransTransactionId,
                    $transactionStatus
                );
            }

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            Log::error('Pemrosesan notifikasi Midtrans gagal.', [
                'order_id' => $orderId,
                'midtrans_transaction_id' => $midtransTransactionId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Notifikasi gagal diproses.',
            ], 500);
        }
    }

    /**
     * PEMBAYARAN PERPANJANGAN BERHASIL
     *
     * Durasi booking hanya diperbarui satu kali.
     */
    private function markExtensionPaymentAsPaid(
        string $orderId,
        string $midtransTransactionId,
        string $grossAmount
    ): void {
        DB::transaction(function () use (
            $orderId,
            $midtransTransactionId,
            $grossAmount
        ): void {
            $payment = $this->findPaymentByOrderId($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$payment->booking_extension_id) {
                throw new RuntimeException(
                    'Transaksi ini bukan pembayaran perpanjangan.'
                );
            }

            $extension = BookingExtension::whereKey(
                $payment->booking_extension_id
            )->lockForUpdate()->firstOrFail();

            $booking = Booking::whereKey($extension->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            $notifiedMinor = $this->toMinorUnits($grossAmount);
            $paymentMinor = $this->toMinorUnits(
                (string) $payment->amount
            );
            $extensionMinor = $this->toMinorUnits(
                (string) $extension->amount
            );

            if (
                $notifiedMinor !== $paymentMinor
                || $paymentMinor !== $extensionMinor
            ) {
                throw new RuntimeException(
                    'Nominal pembayaran perpanjangan tidak cocok.'
                );
            }

            // Callback sukses berulang tidak menambah jam lagi.
            if (
                $extension->status === 'Lunas'
                && $extension->processed_at !== null
            ) {
                return;
            }

            if ($extension->status !== 'Menunggu') {
                throw new RuntimeException(
                    'Status perpanjangan tidak lagi menunggu pembayaran.'
                );
            }

            if ($booking->status !== 'Lunas') {
                throw new RuntimeException(
                    'Booking awal tidak dalam status Lunas.'
                );
            }

            // Pastikan waktu akhir booking belum berubah.
            if (
                substr((string) $booking->end_time, 0, 8)
                !== substr((string) $extension->previous_end_time, 0, 8)
            ) {
                throw new RuntimeException(
                    'Waktu akhir booking sudah berubah. Perpanjangan perlu diperiksa.'
                );
            }

            // Terapkan tambahan durasi.
            $booking->end_time = $extension->new_end_time;
            $booking->hours = (int) $booking->hours
                + (int) $extension->hours;
            $booking->total_price = (float) $booking->total_price
                + (float) $extension->amount;
            $booking->save();

            $extension->status = 'Lunas';
            $extension->processed_at = now();
            $extension->save();

            $payment->midtrans_order_id = $orderId;
            $payment->transaction_id = $midtransTransactionId;
            $payment->status = 'Lunas';
            $payment->paid_at = $payment->paid_at ?? now();

            // Tidak memberikan poin untuk pembayaran perpanjangan.
            $payment->reward_points_awarded = false;
            $payment->save();

            $user = User::find($booking->user_id);

            if ($user) {
                $user->notify(new ArenaUserNotification(
                    'Perpanjangan Booking Berhasil',
                    'Perpanjangan booking #' . $booking->id
                        . ' berhasil dibayar. Jam selesai sekarang '
                        . $extension->new_end_time . '.',
                    route('user.bookings', [], false)
                ));
            }

            Log::info('Perpanjangan booking berhasil diterapkan.', [
                'extension_id' => $extension->id,
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'new_end_time' => $extension->new_end_time,
            ]);
        }, 3);
    }

    /**
     * PEMBAYARAN GAGAL / MENUNGGU
     */
    private function handleUnsuccessfulPayment(
        string $orderId,
        string $midtransTransactionId,
        string $transactionStatus
    ): void {
        DB::transaction(function () use (
            $orderId,
            $midtransTransactionId,
            $transactionStatus
        ): void {
            $payment = $this->findPaymentByOrderId($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            // Pembayaran perpanjangan memiliki alur status sendiri.
            if ($payment->booking_extension_id) {
                $extension = BookingExtension::whereKey(
                    $payment->booking_extension_id
                )->lockForUpdate()->firstOrFail();

                if (
                    $extension->status === 'Lunas'
                    || $extension->processed_at !== null
                ) {
                    return;
                }

                $payment->midtrans_order_id = $orderId;
                $payment->transaction_id = $midtransTransactionId;

                if ($transactionStatus === 'pending') {
                    if ($extension->status === 'Menunggu') {
                        $payment->status = 'Menunggu';
                        $payment->save();
                    }

                    return;
                }

                if (in_array(
                    $transactionStatus,
                    ['deny', 'cancel', 'expire', 'failure'],
                    true
                )) {
                    $payment->status = 'Gagal';
                    $payment->save();

                    $extension->status = 'Gagal';
                    $extension->save();

                    // Booking awal tetap Lunas.
                    $user = User::find($extension->user_id);

                    if ($user) {
                        $user->notify(new ArenaUserNotification(
                            'Pembayaran Perpanjangan Gagal',
                            'Pembayaran perpanjangan booking #'
                                . $extension->booking_id
                                . ' gagal atau kedaluwarsa. Booking awal tetap berlaku.',
                            route('user.bookings', [], false)
                        ));
                    }
                }

                return;
            }

            // Alur kegagalan booking awal tetap seperti sebelumnya.
            $booking = Booking::whereKey($payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $payment->status === 'Lunas'
                || $booking->status === 'Lunas'
            ) {
                return;
            }

            $payment->midtrans_order_id = $orderId;
            $payment->transaction_id = $midtransTransactionId;

            if ($transactionStatus === 'pending') {
                if (
                    $payment->status === 'Gagal'
                    || $booking->status === 'Dibatalkan'
                ) {
                    return;
                }

                $payment->status = 'Menunggu';
                $payment->save();

                return;
            }

            if (in_array(
                $transactionStatus,
                ['deny', 'cancel', 'expire', 'failure'],
                true
            )) {
                if (
                    $payment->status === 'Gagal'
                    && $booking->status === 'Dibatalkan'
                ) {
                    return;
                }

                $payment->status = 'Gagal';
                $payment->save();

                $booking->status = 'Dibatalkan';
                $booking->save();

                $user = User::find($booking->user_id);

                if ($user) {
                    $label = $transactionStatus === 'expire'
                        ? 'Kedaluwarsa'
                        : (
                            $transactionStatus === 'cancel'
                                ? 'Dibatalkan'
                                : 'Gagal'
                        );

                    $user->notify(new ArenaUserNotification(
                        'Pembayaran ' . $label,
                        'Pembayaran booking #' . $booking->id
                            . ' ' . strtolower($label) . '.',
                        route('user.payments', [], false)
                    ));
                }
            }
        }, 3);
    }

    /**
     * PEMBAYARAN BOOKING AWAL BERHASIL
     */
    private function markPaymentAsPaid(
        string $orderId,
        string $midtransTransactionId,
        string $grossAmount
    ): void {
        DB::transaction(function () use (
            $orderId,
            $midtransTransactionId,
            $grossAmount
        ): void {
            $payment = $this->findPaymentByOrderId($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            // Mencegah pembayaran extension masuk ke alur poin booking awal.
            if ($payment->booking_extension_id) {
                throw new RuntimeException(
                    'Pembayaran ini merupakan pembayaran perpanjangan.'
                );
            }

            $booking = Booking::whereKey($payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            $notifiedMinor = $this->toMinorUnits($grossAmount);
            $bookingMinor = $this->toMinorUnits(
                (string) $booking->total_price
            );
            $paymentMinor = $this->toMinorUnits(
                (string) $payment->amount
            );

            if (
                $notifiedMinor !== $bookingMinor
                || $paymentMinor !== $bookingMinor
            ) {
                throw new RuntimeException(
                    'Nominal Midtrans tidak cocok dengan total booking.'
                );
            }

            $user = User::whereKey($booking->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyPaid = $payment->status === 'Lunas'
                && $booking->status === 'Lunas';

            // Poin hanya diberikan sekali.
            if (!$payment->reward_points_awarded) {
                $user->increment('points', 10);
                $payment->reward_points_awarded = true;
            }

            if ($alreadyPaid) {
                $payment->save();
                return;
            }

            $payment->midtrans_order_id = $orderId;
            $payment->transaction_id = $midtransTransactionId;
            $payment->status = 'Lunas';
            $payment->paid_at = $payment->paid_at ?? now();
            $payment->save();

            $booking->status = 'Lunas';
            $booking->save();

            Log::info('Pembayaran booking berhasil disinkronkan.', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'order_id' => $orderId,
            ]);

            $user->notify(new ArenaUserNotification(
                'Pembayaran Berhasil',
                'Pembayaran booking #' . $booking->id
                    . ' berhasil. Anda mendapatkan 10 poin.',
                route('user.bookings', [], false)
            ));
        }, 3);
    }

    /**
     * CARI PEMBAYARAN BERDASARKAN ORDER ID
     */
    private function findPaymentByOrderId(string $orderId)
    {
        return Payment::where('midtrans_order_id', $orderId)
            ->orWhere(function ($query) use ($orderId): void {
                $query->whereNull('midtrans_order_id')
                    ->where('transaction_id', $orderId);
            });
    }

    /**
     * KONFIGURASI MIDTRANS
     */
    private function configureMidtrans(
        string $serverKey,
        bool $isProduction
    ): void {
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = $isProduction;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    /**
     * CEK STATUS BERHASIL
     */
    private function isSuccessfulStatus(
        string $statusCode,
        string $transactionStatus,
        string $fraudStatus
    ): bool {
        return (
            $transactionStatus === 'settlement'
            && $statusCode === '200'
        ) || (
            $transactionStatus === 'capture'
            && $statusCode === '200'
            && in_array($fraudStatus, ['', 'accept'], true)
        );
    }

    /**
     * KONVERSI NOMINAL KE SATUAN MINOR
     */
    private function toMinorUnits(string $amount): int
    {
        if (!is_numeric($amount)) {
            throw new RuntimeException(
                'Nominal Midtrans tidak valid.'
            );
        }

        return (int) round(((float) $amount) * 100);
    }
}