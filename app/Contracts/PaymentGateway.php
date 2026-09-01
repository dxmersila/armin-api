<?php

namespace App\Contracts;

use App\Contracts\Payments\PaymentGatewayCallback;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Models\Payment;

interface PaymentGateway
{
    public function name(): string;

    public function initiate(
        Payment $payment
    ): PaymentInitiationResult;

    public function verify(
        Payment $payment,
        PaymentGatewayCallback $callback
    ): PaymentGatewayResult;
}
