<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGateway
{
    public function pay(Payment $payment): bool;
}
