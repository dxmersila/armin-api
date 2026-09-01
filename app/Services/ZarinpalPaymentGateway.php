<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayCallback;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZarinpalPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'zarinpal';
    }

    public function initiate(Payment $payment): PaymentInitiationResult
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->post(
                    config('payment.zarinpal.request_url'),
                    [
                        'merchant_id' => config('payment.zarinpal.merchant_id'),
                        'amount' => $payment->amount,
                        'callback_url' => rtrim(
                            config('payment.zarinpal.callback_url'),
                            '/'
                        ) . '/' . $payment->id . '/callback',
                        'description' => 'Payment #' . $payment->id,
                    ]
                );
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'ZarinPal payment request failed.',
                previous: $exception
            );
        }

        $data = $response->json();

        if (
            ! $response->successful()
            || ($data['data']['code'] ?? null) !== 100
            || empty($data['data']['authority'])
        ) {
            throw new RuntimeException(
                'ZarinPal payment request failed.'
            );
        }

        $authority = $data['data']['authority'];

        return new PaymentInitiationResult(
            redirectUrl: rtrim(
                config('payment.zarinpal.payment_url'),
                '/'
            ) . '/' . $authority,
            reference: $authority,
        );
    }

    public function verify(
        Payment $payment,
        PaymentGatewayCallback $callback
    ): PaymentGatewayResult {
        if ($callback->status !== 'OK') {
            return new PaymentGatewayResult(
                successful: false,
                transactionId: null,
            );
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->post(
                    config('payment.zarinpal.verify_url'),
                    [
                        'merchant_id' => config('payment.zarinpal.merchant_id'),
                        'authority' => $callback->reference,
                        'amount' => $payment->amount,
                    ]
                );
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'ZarinPal payment verification failed.',
                previous: $exception
            );
        }

        $data = $response->json();

        $code = $data['data']['code'] ?? null;

        if (
            $response->successful()
            && in_array($code, [100, 101], true)
        ) {
            $refId = $data['data']['ref_id'] ?? null;

            if ($refId === null) {
                throw new RuntimeException(
                    'ZarinPal verification response is missing ref_id.'
                );
            }

            return new PaymentGatewayResult(
                successful: true,
                transactionId: (string) $refId,
            );
        }

        return new PaymentGatewayResult(
            successful: false,
            transactionId: null,
        );
    }
}
