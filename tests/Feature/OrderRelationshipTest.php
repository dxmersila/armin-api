<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_belongs_to_user(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($order->user_id, $order->user->id);
    }

    public function test_user_has_many_orders(): void
    {
        $user = User::factory()->create();

        Order::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $user->orders);
        $this->assertTrue(
            $user->orders->every(
                fn(Order $order) => $order->user_id === $user->id
            )
        );
    }

    public function test_order_has_many_order_items(): void
    {
        $order = Order::factory()->create();

        OrderItem::factory()->count(2)->create([
            'order_id' => $order->id,
        ]);

        $this->assertCount(2, $order->orderItems);
        $this->assertTrue(
            $order->orderItems->every(
                fn(OrderItem $orderItem) => $orderItem->order_id === $order->id
            )
        );
    }

    public function test_order_item_belongs_to_order(): void
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertInstanceOf(Order::class, $orderItem->order);
        $this->assertEquals($orderItem->order_id, $orderItem->order->id);
    }

    public function test_order_item_belongs_to_item(): void
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertInstanceOf(Item::class, $orderItem->item);
        $this->assertEquals($orderItem->item_id, $orderItem->item->id);
    }

    public function test_item_has_many_order_items(): void
    {
        $item = Item::factory()->create();

        OrderItem::factory()->count(2)->create([
            'item_id' => $item->id,
        ]);

        $this->assertCount(2, $item->orderItems);
        $this->assertTrue(
            $item->orderItems->every(
                fn(OrderItem $orderItem) => $orderItem->item_id === $item->id
            )
        );
    }

    public function test_order_item_price_is_independent_from_item_price(): void
    {
        $item = Item::factory()->create([
            'price' => 250000,
        ]);

        $orderItem = OrderItem::factory()->create([
            'item_id' => $item->id,
            'price' => 250000,
        ]);

        $item->update([
            'price' => 350000,
        ]);

        $orderItem->refresh();

        $this->assertEquals(250000, $orderItem->price);
        $this->assertEquals(350000, $orderItem->item->price);
    }
}
