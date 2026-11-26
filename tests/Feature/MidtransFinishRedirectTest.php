<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MidtransFinishRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function makePayment(): Payment
    {
        $user = User::factory()->create();

        $lapanganId = DB::table('lapangan')->insertGetId([
            'nama' => 'Lapangan Test',
            'jenis' => 'Futsal',
            'lokasi' => 'Test',
            'harga' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bookingId = DB::table('bookings')->insertGetId([
            'user_id' => $user->id,
            'lapangan_id' => $lapanganId,
            'payment_method' => 'Midtrans',
            'booking_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'hours' => 1,
            'total_price' => 100000,
            'status' => 'Menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Payment::create([
            'booking_id' => $bookingId,
            'user_id' => $user->id,
            'method' => 'Midtrans',
            'amount' => 100000,
            'status' => 'Menunggu',
            'midtrans_order_id' => 'ARENA-PAY-1-TRY-20260101000000-abcdef',
        ]);
    }

    public function test_finish_redirect_sends_owner_to_payment_status(): void
    {
        $payment = $this->makePayment();

        $response = $this->withSession(['admin_user_id' => $payment->user_id])
            ->get(route('midtrans.finish', [
                'order_id' => $payment->midtrans_order_id,
                'status_code' => '200',
                'transaction_status' => 'settlement',
            ]));

        $response->assertRedirect(route('user.payments.status', $payment));
    }

    public function test_finish_redirect_accepts_transaction_id_as_fallback(): void
    {
        $payment = $this->makePayment();
        $payment->update(['transaction_id' => 'TRX-DANA-123']);
        $payment->update(['midtrans_order_id' => 'ARENA-PAY-1-TRY-20260101000000-abcdef']);

        $this->withSession(['admin_user_id' => $payment->user_id])
            ->get(route('midtrans.finish', [
                'order_id' => 'TRX-DANA-123',
                'transaction_id' => 'TRX-DANA-123',
            ]))
            ->assertRedirect(route('user.payments.status', $payment));
    }

    public function test_finish_redirect_requires_login_for_guest(): void
    {
        $payment = $this->makePayment();

        $this->get(route('midtrans.finish', [
            'order_id' => $payment->midtrans_order_id,
        ]))->assertRedirect(route('login'));
    }

    public function test_finish_redirect_rejects_other_users_payment(): void
    {
        $payment = $this->makePayment();
        $otherUser = User::factory()->create();

        $this->withSession(['admin_user_id' => $otherUser->id])
            ->get(route('midtrans.finish', [
                'order_id' => $payment->midtrans_order_id,
            ]))
            ->assertRedirect(route('login'));
    }

    public function test_finish_redirect_handles_unknown_order_id(): void
    {
        $this->withSession(['admin_user_id' => 1])
            ->get(route('midtrans.finish', ['order_id' => 'UNKNOWN']))
            ->assertRedirect(route('user.bookings'))
            ->assertSessionHas('error');
    }

    public function test_finish_redirect_without_order_id_does_not_error(): void
    {
        $this->get(route('midtrans.finish'))
            ->assertRedirect(route('user.bookings'))
            ->assertSessionHas('error');
    }
}
