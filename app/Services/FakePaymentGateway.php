<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Models\Payment;

class FakePaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function pay(Payment $payment): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            successful: true,
            transactionId: 'fake-' . $payment->id,
        );
    }
}
