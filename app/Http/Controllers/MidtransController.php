<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransController extends Controller
{
    public function checkout(Request $request, Booking $booking): RedirectResponse
    {
        $userId = (int) $request->session()->get('admin_user_id');
        abort_unless((int) $booking->user_id === $userId, 403);
        abort_unless(
            config('services.midtrans.server_key') && config('services.midtrans.client_key'),
            503,
            'Midtrans belum dikonfigurasi.'
        );

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        abort_if($payment->status === 'Lunas', 409, 'Booking ini sudah lunas.');

        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = (bool) config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        // One canonical amount: the full booking price stored by the backend.
        $amount = (int) $booking->total_price;
        abort_if($amount < 1, 422, 'Total booking tidak valid.');

        // Reuse a still-existing Snap session rather than creating a new order on refresh.
        if ($payment->snap_token && $payment->transaction_id) {
            $host = config('services.midtrans.is_production')
                ? 'https://app.midtrans.com/snap/v2/vtweb/'
                : 'https://app.sandbox.midtrans.com/snap/v2/vtweb/';
            return redirect()->away($host . $payment->snap_token);
        }

        $customer = User::find($userId);
        // Stable order ID per payment avoids generating a new transaction on refresh.
        $orderId = 'ARENA-PAY-' . $payment->id . '-BOOK-' . $booking->id;

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
            $payment->update([
                'method' => 'Midtrans',
                'amount' => $amount,
                'status' => 'Menunggu',
                'transaction_id' => $orderId,
                'snap_token' => $snap->token,
            ]);
            return redirect()->away($snap->redirect_url);
        } catch (\Throwable $e) {
            Log::error('Midtrans checkout error', [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'message' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal membuat pembayaran Midtrans. Periksa konfigurasi dan log aplikasi.');
        }
    }

    /**
     * Server-to-server notification endpoint. Browser redirects are never payment proof.
     */
    public function notification(Request $request)
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if ($serverKey === '') {
            Log::error('Midtrans notification received without server key configured.');
            return response()->json(['ok' => false], 500);
        }

        // Laravel can receive JSON or form-encoded callbacks depending on proxy/client behavior.
        $payload = $request->all();
        Log::info('Midtrans notification received', [
            'content_type' => $request->header('Content-Type'),
            'order_id' => $payload['order_id'] ?? null,
            'transaction_status' => $payload['transaction_status'] ?? null,
            'status_code' => $payload['status_code'] ?? null,
        ]);
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '') {
            Log::warning('Incomplete Midtrans notification payload.');
            return response()->json(['ok' => false], 400);
        }

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid Midtrans notification signature', ['order_id' => $orderId]);
            return response()->json(['ok' => false], 403);
        }

        try {
            DB::transaction(function () use ($orderId, $statusCode, $grossAmount, $transactionStatus, $payload) {
                $payment = Payment::where('transaction_id', $orderId)->lockForUpdate()->firstOrFail();
                $booking = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

                $toMinorUnits = static function ($value): ?int {
                    if (!is_numeric($value)) return null;
                    return (int) round(((float) $value) * 100);
                };
                $notifiedAmount = $toMinorUnits($grossAmount);
                $bookingAmount = (int) $booking->total_price;
                $paymentAmount = (int) $payment->amount;

                if ($notifiedAmount === null ||
                    $notifiedAmount !== $bookingAmount * 100 ||
                    $paymentAmount !== $bookingAmount) {
                    Log::error('Midtrans amount mismatch', [
                        'order_id' => $orderId,
                        'notified_amount' => $grossAmount,
                        'booking_amount' => $bookingAmount,
                        'payment_amount' => $paymentAmount,
                    ]);
                    throw new \RuntimeException('Midtrans amount does not match the booking total.');
                }

                // A paid transaction is terminal for this flow: old notifications cannot downgrade it.
                if ($payment->status === 'Lunas') {
                    return;
                }

                $fraudStatus = (string) ($payload['fraud_status'] ?? '');
                $isSuccessful = $transactionStatus === 'settlement'
                    || ($transactionStatus === 'capture' && $fraudStatus === 'accept');

                if ($isSuccessful && in_array($statusCode, ['200'], true)) {
                    $user = User::whereKey($booking->user_id)->lockForUpdate()->firstOrFail();

                    $payment->status = 'Lunas';
                    $payment->paid_at = now();
                    // This flag is protected by the payment row lock and updated in this DB transaction.
                    if (!$payment->reward_points_awarded) {
                        $user->increment('points', 10);
                        $payment->reward_points_awarded = true;
                    }
                    $payment->save();

                    $booking->status = 'Lunas';
                    $booking->save();
                    return;
                }

                if ($transactionStatus === 'pending') {
                    if ($payment->status !== 'Lunas') {
                        $payment->status = 'Menunggu';
                        $payment->save();
                    }
                    return;
                }

                if (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'], true)) {
                    $payment->status = 'Gagal';
                    $payment->save();
                    if ($booking->status !== 'Lunas') {
                        $booking->status = 'Dibatalkan';
                        $booking->save();
                    }
                }
            }, 3);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('Midtrans notification processing failed; safe retry required', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);
            // 5xx asks Midtrans to retry. Do not acknowledge a failed DB transaction as success.
            return response()->json(['ok' => false], 500);
        }
    }
}
