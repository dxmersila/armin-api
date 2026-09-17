<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayCallback;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGatewayResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCallbackApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_callback_verifies_successful_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
            'gateway' => 'fake',
            'gateway_reference' => 'ref-123',
        ]);

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->withArgs(function (
                Payment $receivedPayment,
                PaymentGatewayCallback $callback
            ) use ($payment) {
                return $receivedPayment->is($payment)
                    && $callback->reference === 'ref-123'
                    && $callback->status === 'OK';
            })
            ->andReturn(new PaymentGatewayResult(
                successful: true,
                transactionId: 'TX-123',
            ));

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolveByName')
                ->once()
                ->with('fake')
                ->andReturn($gateway);
        });

        config([
            'payment.web_app_url' => 'http://web.test',
        ]);

        $response = $this->get(
            "/api/v1/payments/{$payment->id}/callback"
                . '?Authority=ref-123&Status=OK'
        );

        $response->assertRedirect(
            'http://web.test/payment/result?order_id='
                . $order->id
                . '&payment_id='
                . $payment->id
                . '&status=paid'
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
            'gateway' => 'fake',
            'gateway_transaction_id' => 'TX-123',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PAID->value,
        ]);
    }

    public function test_payment_callback_rejects_mismatched_gateway_reference(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
            'gateway' => 'fake',
            'gateway_reference' => 'expected-ref',
        ]);

        $response = $this->getJson(
            "/api/v1/payments/{$payment->id}/callback"
                . '?Authority=different-ref&Status=OK'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Payment gateway reference does not match the callback reference.'
            );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PENDING->value,
        ]);
    }
}
