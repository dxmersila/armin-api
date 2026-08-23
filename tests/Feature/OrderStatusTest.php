<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_is_pending_by_default(): void
    {
        $order = Order::factory()->create();

        $this->assertSame(
            OrderStatus::PENDING,
            $order->status
        );
    }

    public function test_order_can_have_paid_status(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
        ]);

        $this->assertSame(
            OrderStatus::PAID,
            $order->status
        );
    }

    public function test_order_can_have_failed_status(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::FAILED,
        ]);

        $this->assertSame(
            OrderStatus::FAILED,
            $order->status
        );
    }

    public function test_order_can_have_cancelled_status(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::CANCELLED,
        ]);

        $this->assertSame(
            OrderStatus::CANCELLED,
            $order->status
        );
    }
}
