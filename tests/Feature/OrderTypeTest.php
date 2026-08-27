<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_defaults_to_item_type(): void
    {
        $order = Order::factory()->create();

        $this->assertSame(OrderType::ITEM, $order->type);
    }

    public function test_order_can_have_subscription_type(): void
    {
        $order = Order::factory()->create([
            'type' => OrderType::SUBSCRIPTION,
        ]);

        $this->assertSame(OrderType::SUBSCRIPTION, $order->type);
    }

    public function test_order_type_is_cast_to_enum(): void
    {
        $order = Order::factory()->create([
            'type' => OrderType::SUBSCRIPTION,
        ]);

        $this->assertInstanceOf(OrderType::class, $order->type);
        $this->assertSame(
            OrderType::SUBSCRIPTION,
            $order->type
        );
    }
}
