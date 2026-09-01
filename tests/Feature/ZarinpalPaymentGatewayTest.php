<?php

namespace Tests\Feature;

use App\Contracts\Payments\PaymentGatewayCallback;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Models\Payment;
use App\Services\ZarinpalPaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ZarinpalPaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_initiates_payment_with_zarinpal(): void
    {
        config()->set('payment.zarinpal.merchant_id', 'merchant-test');
        config()->set(
            'payment.zarinpal.request_url',
            'https://api.zarinpal.com/pg/v4/payment/request.json'
        );
        config()->set(
            'payment.zarinpal.payment_url',
            'https://www.zarinpal.com/pg/StartPay/'
        );
        config()->set(
            'payment.zarinpal.callback_url',
            'https://example.test/api/v1/payments'
        );

        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'message' => 'Success',
                    'authority' => 'A000000000000000000000000001234567',
                ],
            ], 200),
        ]);

        $result = app(ZarinpalPaymentGateway::class)
            ->initiate($payment);

        $this->assertInstanceOf(
            PaymentInitiationResult::class,
            $result
        );

        $this->assertSame(
            'A000000000000000000000000001234567',
            $result->reference
        );

        $this->assertSame(
            'https://www.zarinpal.com/pg/StartPay/A000000000000000000000000001234567',
            $result->redirectUrl
        );

        Http::assertSent(function ($request) use ($payment) {
            return $request->url()
                === 'https://api.zarinpal.com/pg/v4/payment/request.json'
                && $request->method() === 'POST'
                && $request['merchant_id'] === 'merchant-test'
                && $request['amount'] === $payment->amount
                && $request['callback_url']
                === 'https://example.test/api/v1/payments/'
                . $payment->id
                . '/callback'
                && $request['description']
                === 'Payment #' . $payment->id;
        });
    }

    public function test_it_verifies_payment_with_zarinpal(): void
    {
        config()->set('payment.zarinpal.merchant_id', 'merchant-test');
        config()->set(
            'payment.zarinpal.verify_url',
            'https://api.zarinpal.com/pg/v4/payment/verify.json'
        );

        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'A000000000000000000000000001234567',
            status: 'OK',
        );

        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => [
                    'code' => 100,
                    'message' => 'Verified',
                    'ref_id' => 987654321,
                ],
            ], 200),
        ]);

        $result = app(ZarinpalPaymentGateway::class)
            ->verify($payment, $callback);

        $this->assertTrue($result->successful);

        $this->assertSame(
            '987654321',
            $result->transactionId
        );

        Http::assertSent(function ($request) use ($payment, $callback) {
            return $request->url()
                === 'https://api.zarinpal.com/pg/v4/payment/verify.json'
                && $request->method() === 'POST'
                && $request['merchant_id'] === 'merchant-test'
                && $request['amount'] === $payment->amount
                && $request['authority'] === $callback->reference;
        });
    }

    public function test_it_treats_already_verified_payment_as_successful(): void
    {
        config()->set('payment.zarinpal.merchant_id', 'merchant-test');
        config()->set(
            'payment.zarinpal.verify_url',
            'https://api.zarinpal.com/pg/v4/payment/verify.json'
        );

        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'A000000000000000000000000001234567',
            status: 'OK',
        );

        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => [
                    'code' => 101,
                    'message' => 'Already Verified',
                    'ref_id' => 987654321,
                ],
            ], 200),
        ]);

        $result = app(ZarinpalPaymentGateway::class)
            ->verify($payment, $callback);

        $this->assertTrue($result->successful);

        $this->assertSame(
            '987654321',
            $result->transactionId
        );
    }

    public function test_it_returns_failed_result_for_unsuccessful_verification(): void
    {
        config()->set('payment.zarinpal.merchant_id', 'merchant-test');
        config()->set(
            'payment.zarinpal.verify_url',
            'https://api.zarinpal.com/pg/v4/payment/verify.json'
        );

        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'A000000000000000000000000001234567',
            status: 'OK',
        );

        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => [
                    'code' => -21,
                    'message' => 'Transaction not found',
                ],
            ], 200),
        ]);

        $result = app(ZarinpalPaymentGateway::class)
            ->verify($payment, $callback);

        $this->assertFalse($result->successful);

        $this->assertNull(
            $result->transactionId
        );
    }

    public function test_it_does_not_verify_when_callback_status_is_not_ok(): void
    {
        config()->set(
            'payment.zarinpal.verify_url',
            'https://api.zarinpal.com/pg/v4/payment/verify.json'
        );

        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'A000000000000000000000000001234567',
            status: 'NOK',
        );

        Http::fake();

        $result = app(ZarinpalPaymentGateway::class)
            ->verify($payment, $callback);

        $this->assertFalse($result->successful);

        $this->assertNull(
            $result->transactionId
        );

        Http::assertNothingSent();
    }

    public function test_it_rejects_already_verified_response_without_ref_id(): void
    {
        config()->set('payment.zarinpal.merchant_id', 'merchant-test');
        config()->set(
            'payment.zarinpal.verify_url',
            'https://api.zarinpal.com/pg/v4/payment/verify.json'
        );

        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'A000000000000000000000000001234567',
            status: 'OK',
        );

        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => [
                    'code' => 101,
                    'message' => 'Already Verified',
                ],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'ZarinPal verification response is missing ref_id.'
        );

        app(ZarinpalPaymentGateway::class)
            ->verify($payment, $callback);
    }

    public function test_it_wraps_zarinpal_connection_failure(): void
    {
        config()->set(
            'payment.zarinpal.merchant_id',
            'merchant-test'
        );

        config()->set(
            'payment.zarinpal.request_url',
            'https://api.zarinpal.com/pg/v4/payment/request.json'
        );

        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        Http::fake([
            'https://api.zarinpal.com/pg/v4/payment/request.json'
            => function () {
                throw new \Illuminate\Http\Client\ConnectionException(
                    'Connection failed.'
                );
            },
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'ZarinPal payment request failed.'
        );

        app(ZarinpalPaymentGateway::class)
            ->initiate($payment);
    }
}
