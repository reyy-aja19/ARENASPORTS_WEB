<?php

namespace Tests\Unit;

use App\Http\Controllers\MidtransController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MidtransControllerTest extends TestCase
{
    private function invokePrivate(string $method, array $arguments): mixed
    {
        $controller = new MidtransController();
        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $arguments);
    }

    public function test_settlement_200_is_successful(): void
    {
        $this->assertTrue($this->invokePrivate('isSuccessfulStatus', ['200', 'settlement', '']));
    }

    public function test_pending_is_not_successful(): void
    {
        $this->assertFalse($this->invokePrivate('isSuccessfulStatus', ['201', 'pending', '']));
    }

    public function test_capture_requires_accepted_or_empty_fraud_status(): void
    {
        $this->assertTrue($this->invokePrivate('isSuccessfulStatus', ['200', 'capture', 'accept']));
        $this->assertFalse($this->invokePrivate('isSuccessfulStatus', ['200', 'capture', 'challenge']));
    }

    public function test_failed_and_expired_are_not_successful(): void
    {
        foreach (['deny', 'cancel', 'expire', 'failure'] as $status) {
            $this->assertFalse($this->invokePrivate('isSuccessfulStatus', ['200', $status, '']));
        }
    }

    public function test_amounts_are_compared_in_minor_units(): void
    {
        $this->assertSame(
            $this->invokePrivate('toMinorUnits', ['90000.00']),
            $this->invokePrivate('toMinorUnits', ['90000'])
        );
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->invokePrivate('toMinorUnits', ['not-an-amount']);
    }
}
