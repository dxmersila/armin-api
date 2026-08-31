<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\AccessGrantService;
use App\Services\PaymentProcessingService;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Services\PaymentGatewayResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentAccessTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_and_order_are_rolled_back_when_access_grant_fails(): void
    {
        $user = User::factory()->create();

        $itemOne = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
            'is_visible' => true,
        ]);

        $itemTwo = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 200000,
            'is_visible' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 300000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $itemOne->id,
            'price' => 100000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $itemTwo->id,
            'price' => 200000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 300000,
        ]);

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('pay')
            ->once()
            ->with($payment)
            ->andReturn(
                new PaymentGatewayResult(
                    successful: true,
                    transactionId: 'test-transaction-1',
                )
            );

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        $this->mock(AccessGrantService::class, function ($mock) use ($order) {
            $mock
                ->shouldReceive('grantForOrder')
                ->once()
                ->withArgs(function (Order $receivedOrder) use ($order) {
                    return $receivedOrder->is($order);
                })
                ->andThrow(new RuntimeException(
                    'Access grant failed.'
                ));
        });

        try {
            app(PaymentProcessingService::class)
                ->process($payment);

            $this->fail('Expected PaymentProcessingService to throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Access grant failed.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PENDING->value,
        ]);

        $this->assertDatabaseCount('accesses', 0);
    }
}
