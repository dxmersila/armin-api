<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayResolver
{
    public function resolve(): PaymentGateway
    {
        $name = config('payment.gateway');

        $gatewayClass = config("payment.gateways.{$name}");

        if ($gatewayClass === null) {
            throw new InvalidArgumentException(
                "Payment gateway [{$name}] is not configured."
            );
        }

        return app($gatewayClass);
    }
}
