<?php

namespace App\Contracts\Payments;

final readonly class PaymentInitiationResult
{
    public function __construct(
        public string $redirectUrl,
        public string $reference,
    ) {}
}
