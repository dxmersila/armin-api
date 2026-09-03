<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayResolver
{
    public function resolve(): PaymentGateway
    {
        return $this->resolveByName(
            config('payment.gateway')
        );
    }

    public function resolveByName(string $name): PaymentGateway
    {
        $gatewayClass = config("payment.gateways.{$name}");

        if ($gatewayClass === null) {
            throw new InvalidArgumentException(
                "Payment gateway [{$name}] is not configured."
            );
        }

        return app($gatewayClass);
    }
}
