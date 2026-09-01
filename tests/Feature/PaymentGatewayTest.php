<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Models\Payment;
use PHPUnit\Framework\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_payment_gateway_contract_defines_required_methods(): void
    {
        $reflection = new \ReflectionClass(PaymentGateway::class);

        $this->assertTrue(
            $reflection->hasMethod('name')
        );

        $this->assertTrue(
            $reflection->hasMethod('initiate')
        );

        $this->assertTrue(
            $reflection->hasMethod('verify')
        );

        $initiate = $reflection->getMethod('initiate');

        $this->assertSame(
            Payment::class,
            $initiate->getParameters()[0]->getType()->getName()
        );

        $this->assertSame(
            PaymentInitiationResult::class,
            $initiate->getReturnType()->getName()
        );

        $verify = $reflection->getMethod('verify');

        $this->assertSame(
            Payment::class,
            $verify->getParameters()[0]->getType()->getName()
        );

        $this->assertSame(
            PaymentGatewayResult::class,
            $verify->getReturnType()->getName()
        );
    }
}
