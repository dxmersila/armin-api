<?php

use App\Services\FakePaymentGateway;
use App\Services\ZarinpalPaymentGateway;

return [
    'gateway' => env('PAYMENT_GATEWAY', 'fake'),

    'gateways' => [
        'fake' => FakePaymentGateway::class,
        'zarinpal' => ZarinpalPaymentGateway::class,
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),

        'request_url' => env(
            'ZARINPAL_REQUEST_URL',
            'https://api.zarinpal.com/pg/v4/payment/request.json'
        ),

        'verify_url' => env(
            'ZARINPAL_VERIFY_URL',
            'https://api.zarinpal.com/pg/v4/payment/verify.json'
        ),

        'payment_url' => env(
            'ZARINPAL_PAYMENT_URL',
            'https://www.zarinpal.com/pg/StartPay/'
        ),

        'callback_url' => env('ZARINPAL_CALLBACK_URL'),
    ],
];
