<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_order_total_price(): void
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

        $service = new OrderPricingService();

        $this->assertEquals(
            400000,
            $service->calculate($order)
        );
    }

    public function test_it_updates_order_total_price(): void
    {
        $order = Order::factory()->create([
            'total_price' => 0,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 250000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 150000,
        ]);

        $service = new OrderPricingService();

        $totalPrice = $service->update($order);

        $order->refresh();

        $this->assertEquals(400000, $totalPrice);
        $this->assertEquals(400000, $order->total_price);
    }

    public function test_order_without_items_has_zero_total_price(): void
    {
        $order = Order::factory()->create([
            'total_price' => 0,
        ]);

        $service = new OrderPricingService();

        $this->assertEquals(
            0,
            $service->calculate($order)
        );
    }
}
