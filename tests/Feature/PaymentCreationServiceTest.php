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
}
