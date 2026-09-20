<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\PaymentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_payment_for_order(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = app(PaymentCreationService::class)
            ->create($order);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_payment_amount_equals_order_total_price(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 250000,
        ]);

        $payment = app(PaymentCreationService::class)
            ->create($order);

        $this->assertSame(
            250000,
            $payment->amount
        );
    }

    public function test_new_payment_is_pending(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 90000,
        ]);

        $payment = app(PaymentCreationService::class)
            ->create($order);

        $this->assertSame(
            PaymentStatus::PENDING,
            $payment->status
        );
    }

    public function test_it_rejects_non_pending_orders(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
            'total_price' => 100000,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(PaymentCreationService::class)
            ->create($order);
    }

    public function test_it_rejects_zero_total_price_order(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 0,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(PaymentCreationService::class)
            ->create($order);
    }

    public function test_it_removes_hidden_items_before_creating_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 300000,
        ]);

        $visibleItem = \App\Models\Item::factory()->create([
            'is_visible' => true,
            'access_type' => 'paid',
            'price' => 200000,
        ]);

        $hiddenItem = \App\Models\Item::factory()->create([
            'is_visible' => false,
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        \App\Models\OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $visibleItem->id,
            'price' => 200000,
        ]);

        \App\Models\OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $hiddenItem->id,
            'price' => 100000,
        ]);

        $payment = app(PaymentCreationService::class)
            ->create($order);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'order_id' => $order->id,
            'amount' => 200000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_id' => $visibleItem->id,
        ]);

        $this->assertDatabaseMissing('order_items', [
            'order_id' => $order->id,
            'item_id' => $hiddenItem->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'total_price' => 200000,
        ]);
    }

    public function test_it_rejects_payment_when_all_order_items_are_hidden(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 100000,
        ]);

        $item = \App\Models\Item::factory()->create([
            'is_visible' => false,
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        \App\Models\OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => 100000,
        ]);

        try {
            app(PaymentCreationService::class)
                ->create($order);

            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Order has no available items to purchase.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseMissing('payments', [
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'total_price' => 0,
        ]);
    }

}
