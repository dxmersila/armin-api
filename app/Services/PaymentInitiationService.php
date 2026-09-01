<?php

namespace App\Services;

use App\Contracts\Payments\PaymentInitiationResult;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use InvalidArgumentException;

class PaymentInitiationService
{
    public function __construct(
        protected PaymentGatewayResolver $gatewayResolver,
    ) {}

    public function initiate(Payment $payment): PaymentInitiationResult
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            throw new InvalidArgumentException(
                'Only pending payments can be initiated.'
            );
        }

        $gateway = $this->gatewayResolver->resolve();

        $result = $gateway->initiate($payment);

        $payment->update([
            'gateway' => $gateway->name(),
            'gateway_reference' => $result->reference,
        ]);

        return $result;
    }
}
