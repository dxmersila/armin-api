<?php

namespace App\Contracts\Payments;

final readonly class PaymentGatewayCallback
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $reference,
        public string $status,
        public array $metadata = [],
    ) {}
}
