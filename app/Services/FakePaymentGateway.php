<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayCallback;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Models\Payment;

class FakePaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function initiate(Payment $payment): PaymentInitiationResult
    {
        return new PaymentInitiationResult(
            redirectUrl: 'https://gateway.test/pay/' . $payment->id,
            reference: 'fake-reference-' . $payment->id,
        );
    }

    public function verify(
        Payment $payment,
        PaymentGatewayCallback $callback
    ): PaymentGatewayResult {
        return new PaymentGatewayResult(
            successful: true,
            transactionId: 'fake-' . $payment->id,
        );
    }
}
