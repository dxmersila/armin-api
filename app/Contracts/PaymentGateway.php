<?php

namespace App\Contracts;

use App\Contracts\Payments\PaymentGatewayResult;
use App\Models\Payment;

interface PaymentGateway
{
    public function pay(Payment $payment): PaymentGatewayResult;
}
