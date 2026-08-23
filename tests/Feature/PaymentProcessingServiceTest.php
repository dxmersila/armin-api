<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_payment_marks_payment_and_order_as_paid(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('pay')
            ->once()
            ->with($payment)
            ->andReturn(true);

        $result = app(PaymentProcessingService::class)
            ->process($payment);

        $this->assertSame(
            PaymentStatus::PAID,
            $result->status
        );

        $this->assertSame(
            OrderStatus::PAID,
            $result->order->status
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PAID->value,
        ]);
    }

    public function test_failed_payment_marks_payment_as_failed_and_keeps_order_pending(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('pay')
            ->once()
            ->with($payment)
            ->andReturn(false);

        $result = app(PaymentProcessingService::class)
            ->process($payment);

        $this->assertSame(
            PaymentStatus::FAILED,
            $result->status
        );

        $this->assertSame(
            OrderStatus::PENDING,
            $result->order->status
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::FAILED->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PENDING->value,
        ]);
    }

    public function test_paid_payment_cannot_be_processed_again(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PAID,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PAID,
            'amount' => 150000,
        ]);

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldNotReceive('pay');

        $this->expectException(InvalidArgumentException::class);

        app(PaymentProcessingService::class)
            ->process($payment);
    }

    public function test_processing_payment_requires_pending_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::FAILED,
            'amount' => 150000,
        ]);

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldNotReceive('pay');

        $this->expectException(InvalidArgumentException::class);

        app(PaymentProcessingService::class)
            ->process($payment);
    }
}
