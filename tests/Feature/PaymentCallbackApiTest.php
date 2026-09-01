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
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        $response = $this->getJson(
            "/api/v1/payments/{$payment->id}/callback"
                . '?Authority=ref-123&Status=OK'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $payment->id
            )
            ->assertJsonPath(
                'data.status',
                PaymentStatus::PAID->value
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
}
