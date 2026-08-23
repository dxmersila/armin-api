<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Models\Payment;
use PHPUnit\Framework\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_payment_gateway_contract_defines_pay_method(): void
    {
        $reflection = new \ReflectionClass(PaymentGateway::class);

        $this->assertTrue(
            $reflection->hasMethod('pay')
        );

        $method = $reflection->getMethod('pay');

        $this->assertSame(
            Payment::class,
            $method->getParameters()[0]->getType()->getName()
        );

        $this->assertSame(
            'bool',
            $method->getReturnType()->getName()
        );
    }
}
