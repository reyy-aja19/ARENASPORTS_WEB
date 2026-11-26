<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-TESTKEY';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $orderId = (string) ($overrides['order_id'] ?? 'test-notification-123');
        $statusCode = (string) ($overrides['status_code'] ?? '200');
        $grossAmount = (string) ($overrides['gross_amount'] ?? '10000.00');

        $payload = [
            'order_id' => $orderId,
            'transaction_id' => 'trx-test-123',
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => hash(
                'sha512',
                $orderId . $statusCode . $grossAmount . self::SERVER_KEY
            ),
        ];

        return array_merge($payload, $overrides);
    }

    public function test_test_notification_for_unknown_order_is_acknowledged(): void
    {
        $this->postJson(route('midtrans.notification'), $this->payload())
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_notification_without_transaction_id_is_acknowledged(): void
    {
        $payload = $this->payload();
        unset($payload['transaction_id']);

        $this->postJson(route('midtrans.notification'), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $payload = $this->payload(['signature_key' => str_repeat('a', 128)]);

        $this->postJson(route('midtrans.notification'), $payload)
            ->assertStatus(403)
            ->assertJson(['ok' => false]);
    }

    public function test_incomplete_payload_is_rejected(): void
    {
        $payload = $this->payload();
        unset($payload['order_id']);

        $this->postJson(route('midtrans.notification'), $payload)
            ->assertStatus(400)
            ->assertJson(['ok' => false]);
    }
}
