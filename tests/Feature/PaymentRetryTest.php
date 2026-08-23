<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_payment_can_be_retried(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 200000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::FAILED,
            'amount' => 200000,
        ]);

        $payment = app(PaymentCreationService::class)
            ->create($order);

        $this->assertSame(
            PaymentStatus::PENDING,
            $payment->status
        );

        $this->assertSame(
            2,
            $order->payments()->count()
        );
    }

    public function test_pending_payment_cannot_be_retried(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 200000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 200000,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(PaymentCreationService::class)
            ->create($order);
    }

    public function test_paid_order_cannot_create_new_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
            'total_price' => 200000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PAID,
            'amount' => 200000,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(PaymentCreationService::class)
            ->create($order);
    }
}
