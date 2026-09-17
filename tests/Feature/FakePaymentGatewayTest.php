<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\FakePaymentGateway;
use App\Contracts\Payments\PaymentGatewayCallback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_fake_gateway_has_expected_name(): void
    {
        $gateway = app(FakePaymentGateway::class);

        $this->assertSame(
            'fake',
            $gateway->name()
        );
    }

    public function test_fake_gateway_successfully_verifies_payment(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'fake-reference-' . $payment->id,
            status: 'OK',
        );

        $result = app(FakePaymentGateway::class)->verify(
            $payment,
            $callback
        );

        $this->assertTrue($result->successful);

        $this->assertSame(
            'fake-' . $payment->id,
            $result->transactionId
        );
    }

    public function test_fake_gateway_rejects_failed_payment_callback(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'fake-reference-' . $payment->id,
            status: 'NOK',
        );

        $result = app(FakePaymentGateway::class)->verify(
            $payment,
            $callback
        );

        $this->assertFalse($result->successful);
        $this->assertNull($result->transactionId);
    }

    public function test_fake_gateway_can_initiate_payment(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 150000,
        ]);

        $result = app(FakePaymentGateway::class)->initiate($payment);

        $this->assertSame(
            'https://gateway.test/pay/' . $payment->id,
            $result->redirectUrl
        );

        $this->assertSame(
            'fake-reference-' . $payment->id,
            $result->reference
        );
    }
}
