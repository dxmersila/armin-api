<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayCallback;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGatewayResolver;
use App\Services\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAccessGrantTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_payment_grants_access_to_all_order_items(): void
    {
        $user = User::factory()->create();

        $itemOne = Item::factory()->create([
            'price' => 100000,
        ]);

        $itemTwo = Item::factory()->create([
            'price' => 200000,
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
            'gateway' => 'fake',
            'gateway_reference' => 'ref-123',
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'ref-123',
            status: 'OK',
        );

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->with($payment, $callback)
            ->andReturn(
                new PaymentGatewayResult(
                    successful: true,
                    transactionId: 'test-transaction-1',
                )
            );

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolveByName')
                ->once()
                ->with('fake')
                ->andReturn($gateway);
        });

        app(PaymentProcessingService::class)
            ->process($payment, $callback);

        $this->assertDatabaseHas('accesses', [
            'user_id' => $user->id,
            'item_id' => $itemOne->id,
        ]);

        $this->assertDatabaseHas('accesses', [
            'user_id' => $user->id,
            'item_id' => $itemTwo->id,
        ]);
    }

    public function test_failed_payment_does_not_grant_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'price' => 100000,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 100000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 100000,
            'gateway' => 'fake',
            'gateway_reference' => 'ref-123',
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'ref-123',
            status: 'OK',
        );

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->with($payment, $callback)
            ->andReturn(
                new PaymentGatewayResult(
                    successful: false,
                    transactionId: null,
                )
            );

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolveByName')
                ->once()
                ->with('fake')
                ->andReturn($gateway);
        });

        app(PaymentProcessingService::class)
            ->process($payment, $callback);

        $this->assertDatabaseMissing('accesses', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }
}
