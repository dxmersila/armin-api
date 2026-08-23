<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_total_price_equals_sum_of_order_item_prices(): void
    {
        $order = Order::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 250000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 150000,
        ]);

        $this->assertEquals(400000, $order->orderItems->sum('price'));
    }

    public function test_order_item_price_is_snapshot_of_item_price(): void
    {
        $item = Item::factory()->create([
            'price' => 250000,
        ]);

        $order = Order::factory()->create();

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => $item->price,
        ]);

        $item->update([
            'price' => 350000,
        ]);

        $orderItem->refresh();

        $this->assertEquals(250000, $orderItem->price);
        $this->assertEquals(350000, $orderItem->item->price);
    }

    public function test_free_item_has_zero_order_item_price(): void
    {
        $item = Item::factory()->create([
            'price' => 0,
        ]);

        $order = Order::factory()->create();

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => 0,
        ]);

        $this->assertEquals(0, $orderItem->price);
        $this->assertEquals(0, $item->price);
    }
}
