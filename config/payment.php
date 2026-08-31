<?php

use App\Services\FakePaymentGateway;

return [
    'gateway' => env('PAYMENT_GATEWAY', 'fake'),

    'gateways' => [
        'fake' => FakePaymentGateway::class,
    ],
];
