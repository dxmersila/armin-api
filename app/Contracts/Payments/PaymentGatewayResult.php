<?php

namespace App\Contracts\Payments;

final readonly class PaymentGatewayResult
{
    public function __construct(
        public bool $successful,
        public ?string $transactionId = null,
    ) {}
}
