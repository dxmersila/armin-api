<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Services\FakePaymentGateway;
use App\Services\PaymentGatewayResolver;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayResolverTest extends TestCase
{
    public function test_it_resolves_the_default_payment_gateway(): void
    {
        $resolver = app(PaymentGatewayResolver::class);

        $gateway = $resolver->resolve();

        $this->assertInstanceOf(
            PaymentGateway::class,
            $gateway
        );

        $this->assertInstanceOf(
            FakePaymentGateway::class,
            $gateway
        );
    }

    public function test_it_rejects_unknown_payment_gateway(): void
    {
        config()->set('payment.gateway', 'unknown');

        $resolver = app(PaymentGatewayResolver::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Payment gateway [unknown] is not configured.'
        );

        $resolver->resolve();
    }
}
