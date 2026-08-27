<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\OrderType;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionCreationService;
use InvalidArgumentException;
use RuntimeException;
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

    public function test_successful_subscription_payment_creates_subscription(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'type' => OrderType::SUBSCRIPTION,
            'total_price' => 300000,
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order->update([
            'subscription_plan_id' => $plan->id,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 300000,
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

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $order->user_id,
            'subscription_plan_id' => $plan->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_subscription_creation_failure_rolls_back_payment_and_order(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'type' => OrderType::SUBSCRIPTION,
            'total_price' => 300000,
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order->update([
            'subscription_plan_id' => $plan->id,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 300000,
        ]);

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('pay')
            ->once()
            ->with($payment)
            ->andReturn(true);

        $subscriptionCreationService = $this->mock(
            SubscriptionCreationService::class
        );

        $subscriptionCreationService
            ->shouldReceive('create')
            ->once()
            ->andThrow(new RuntimeException('Subscription creation failed.'));

        try {
            app(PaymentProcessingService::class)
                ->process($payment);

            $this->fail('Expected subscription creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Subscription creation failed.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PENDING->value,
        ]);

        $this->assertDatabaseMissing('subscriptions', [
            'order_id' => $order->id,
        ]);
    }
}
