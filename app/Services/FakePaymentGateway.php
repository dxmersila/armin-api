<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Payment;

class FakePaymentGateway implements PaymentGateway
{
    public function pay(Payment $payment): bool
    {
        return true;
    }
}
