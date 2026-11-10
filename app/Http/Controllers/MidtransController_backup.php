<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use App\Notifications\ArenaUserNotification;

class MidtransController extends Controller
{
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

        $payment = Payment::where('booking_id', $booking->id)
            ->firstOrFail();

        abort_unless(
            strtolower((string) $payment->method) === 'midtrans',
            409,
            'Booking ini menggunakan pembayaran Cash, bukan Midtrans.'
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

        /*
         * Gunakan Snap token yang sudah ada.
         * Jangan membuat transaksi baru secara otomatis jika transaksi
         * sebelumnya masih perlu direkonsiliasi.
         */
        if (
            $payment->snap_token
            && ($payment->midtrans_order_id || $payment->transaction_id)
        ) {
            $host = $isProduction
                ? 'https://app.midtrans.com/snap/v2/vtweb/'
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/';

            return redirect()->away(
                $host . $payment->snap_token
            );
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
                    'Data pembayaran berubah. Periksa status transaksi sebelum mencoba lagi.'
                );
            }

            return redirect()->away($snap->redirect_url);
        } catch (Throwable $e) {
            Log::error('Midtrans checkout error', [
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

        /*
         * Validasi signature berdasarkan payload asli notifikasi.
         * Jangan menggunakan order_id hasil respons API status di sini.
         */
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

        Log::info('Notifikasi Midtrans diterima.', [
            'order_id' => $orderId,
            'midtrans_transaction_id' => $midtransTransactionId,
            'transaction_status' => $transactionStatus,
        ]);

        /*
         * Pastikan Order ID dari notifikasi menunjuk ke pembayaran lokal.
         */
        if (!$this->findPaymentByOrderId($orderId)->exists()) {
            Log::warning('Order ID Midtrans tidak ditemukan di database.', [
                'order_id' => $orderId,
                'midtrans_transaction_id' => $midtransTransactionId,
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

            /*
             * PENTING:
             * Gunakan transaction_id dari notifikasi untuk meminta status.
             * Pada transaksi DANA yang sudah diperiksa, API mengembalikan
             * order_id yang sama dengan transaction_id, bukan Order ID lokal.
             * Memanggil status menggunakan Order ID lokal menghasilkan 404
             * pada transaksi tersebut.
             */
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

            /*
             * Transaction ID dari API harus cocok dengan notifikasi.
             */
            if (
                $verifiedTransactionId === ''
                || $verifiedTransactionId !== $midtransTransactionId
            ) {
                throw new RuntimeException(
                    'Transaction ID dari API Midtrans tidak cocok.'
                );
            }

            /*
             * Normalnya API mengembalikan Order ID lokal.
             * Pengecualian hanya untuk pola DANA yang teramati:
             * payment_type=dana dan order_id=transaction_id.
             *
             * Kecocokan pembayaran lokal tetap diperiksa melalui $orderId
             * asli dari notifikasi yang signature-nya telah divalidasi.
             */
            $isDanaOrderIdFallback =
                $verifiedPaymentType === 'dana'
                && $verifiedOrderId === $verifiedTransactionId
                && $verifiedTransactionId === $midtransTransactionId;

            if (
                $verifiedOrderId !== $orderId
                && !$isDanaOrderIdFallback
            ) {
                throw new RuntimeException(
                    'Order ID dari API Midtrans tidak cocok dengan order lokal.'
                );
            }

            if ($verifiedStatusCode !== $statusCode) {
                throw new RuntimeException(
                    'Status code notifikasi tidak cocok dengan API Midtrans.'
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

            /*
             * Keputusan status menggunakan data API yang sudah diverifikasi.
             */
            $transactionStatus = $verifiedStatus;
            $fraudStatus = $verifiedFraud;
            $statusCode = $verifiedStatusCode;
            $grossAmount = $verifiedGrossAmount;

            if ($this->isSuccessfulStatus(
                $statusCode,
                $transactionStatus,
                $fraudStatus
            )) {
                $this->markPaymentAsPaid(
                    $orderId,
                    $midtransTransactionId,
                    $grossAmount
                );
            } else {
                DB::transaction(function () use (
                    $orderId,
                    $midtransTransactionId,
                    $transactionStatus
                ): ?array {
                    $payment = $this->findPaymentByOrderId($orderId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $booking = Booking::whereKey($payment->booking_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    // Pembayaran yang sudah lunas tidak boleh diturunkan statusnya.
                    if ($payment->status === 'Lunas' || $booking->status === 'Lunas') {
                        return null;
                    }

                    $payment->midtrans_order_id = $orderId;
                    $payment->transaction_id = $midtransTransactionId;

                    if ($transactionStatus === 'pending') {
                        if ($payment->status === 'Gagal' || $booking->status === 'Dibatalkan') {
                            return null;
                        }
                        $payment->status = 'Menunggu';
                        $payment->save();
                        return null;
                    }

                    if (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true)) {
                        // Callback berulang tidak mengirim notifikasi berulang.
                        if ($payment->status === 'Gagal' && $booking->status === 'Dibatalkan') {
                            return null;
                        }

                        $payment->status = 'Gagal';
                        $payment->save();
                        $booking->status = 'Dibatalkan';
                        $booking->save();

                        $user = User::whereKey($booking->user_id)->firstOrFail();
                        $label = $transactionStatus === 'expire'
                            ? 'Kedaluwarsa'
                            : ($transactionStatus === 'cancel' ? 'Dibatalkan' : 'Gagal');
                        $user->notify(new ArenaUserNotification(
                            'Pembayaran ' . $label,
                            'Pembayaran untuk booking #' . $booking->id . ' ' . strtolower($label) . '. Silakan buat booking baru jika masih ingin bermain.',
                            route('user.payments', [], false)
                        ));
                        return null;
                    }

                    $payment->save();
                    return null;
                }, 3);


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
     * Cari pembayaran berdasarkan Order ID lokal dari Midtrans.
     * Fallback hanya untuk data lama tanpa midtrans_order_id.
     */
    private function findPaymentByOrderId(string $orderId)
    {
        return Payment::where('midtrans_order_id', $orderId)
            ->orWhere(function ($query) use ($orderId): void {
                $query->whereNull('midtrans_order_id')
                    ->where('transaction_id', $orderId);
            });
    }

    private function configureMidtrans(
        string $serverKey,
        bool $isProduction
    ): void {
        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = $isProduction;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

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

    private function toMinorUnits(string $amount): int
    {
        if (!is_numeric($amount)) {
            throw new RuntimeException(
                'Nominal Midtrans tidak valid.'
            );
        }

        return (int) round(((float) $amount) * 100);
    }

    
private function markPaymentAsPaid(
    string $orderId,
    string $midtransTransactionId,
    string $grossAmount
): void {
    DB::transaction(function () use (
        $orderId,
        $midtransTransactionId,
        $grossAmount
    ): ?array {
        $payment = $this->findPaymentByOrderId($orderId)
            ->lockForUpdate()
            ->firstOrFail();

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
            $notifiedMinor !== $bookingMinor ||
            $paymentMinor !== $bookingMinor
        ) {
            throw new RuntimeException(
                'Nominal Midtrans tidak cocok dengan total booking.'
            );
        }

        $user = User::whereKey($booking->user_id)
            ->lockForUpdate()
            ->firstOrFail();

        // Poin diproses terpisah dari status lunas agar data lama yang
        // sudah Lunas tetapi belum mendapat poin dapat diperbaiki juga.
        if (!$payment->reward_points_awarded) {
            $user->increment('points', 10);
            $payment->reward_points_awarded = true;
        }

        // Notifikasi dan perubahan status tidak diulang untuk callback duplikat.
        $alreadyPaid = $payment->status === 'Lunas'
            && $booking->status === 'Lunas';

        if ($alreadyPaid) {
            $payment->save(); // simpan reward_points_awarded jika sebelumnya belum ada
            return null;
        }

        $payment->midtrans_order_id = $orderId;
        $payment->transaction_id = $midtransTransactionId;
        $payment->status = 'Lunas';
        $payment->paid_at = $payment->paid_at ?? now();
        $payment->save();

        $booking->status = 'Lunas';
        $booking->save();

        Log::info('Pembayaran Midtrans berhasil disinkronkan.', [
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'order_id' => $orderId,
        ]);

        $user->notify(new ArenaUserNotification(
            'Pembayaran Berhasil',
            'Pembayaran booking #' . $booking->id . ' telah berhasil. Status booking sekarang Lunas. Anda mendapatkan 10 poin.',
            route('user.bookings', [], false)
        ));

        return null;
    }, 3);
}
}
