<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayCallback;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PaymentGatewayResolver;
use App\Services\PaymentProcessingService;
use App\Services\SubscriptionCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $callback = new PaymentGatewayCallback(
            reference: 'ref-123',
            status: 'OK',
        );

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->with($payment, $callback)
            ->andReturn(new PaymentGatewayResult(
                successful: true,
                transactionId: 'test-transaction-1',
            ));

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        $result = app(PaymentProcessingService::class)
            ->process($payment, $callback);

        $this->assertSame(
            PaymentStatus::PAID,
            $result->status
        );

        $this->assertSame(
            OrderStatus::PAID,
            $result->order->status
        );

        $this->assertSame(
            'test-transaction-1',
            $result->gateway_transaction_id
        );

        $this->assertSame(
            'fake',
            $result->gateway
        );

        $this->assertNotNull($result->paid_at);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
            'gateway' => 'fake',
            'gateway_transaction_id' => 'test-transaction-1',
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

        $callback = new PaymentGatewayCallback(
            reference: 'ref-456',
            status: 'CANCELLED',
        );

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->with($payment, $callback)
            ->andReturn(new PaymentGatewayResult(
                successful: false,
            ));

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        $result = app(PaymentProcessingService::class)
            ->process($payment, $callback);

        $this->assertSame(
            PaymentStatus::FAILED,
            $result->status
        );

        $this->assertSame(
            OrderStatus::PENDING,
            $result->order->status
        );

        $this->assertNull($result->gateway_transaction_id);

        $this->assertSame(
            'fake',
            $result->gateway
        );

        $this->assertNull($result->paid_at);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'gateway' => 'fake',
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
            ->shouldNotReceive('verify');

        $this->expectException(InvalidArgumentException::class);

        app(PaymentProcessingService::class)
            ->process(
                $payment,
                new PaymentGatewayCallback(
                    reference: 'ref-paid',
                    status: 'OK',
                )
            );
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
            ->shouldNotReceive('verify');

        $this->expectException(InvalidArgumentException::class);

        app(PaymentProcessingService::class)
            ->process(
                $payment,
                new PaymentGatewayCallback(
                    reference: 'ref-failed',
                    status: 'FAILED',
                )
            );
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

        $callback = new PaymentGatewayCallback(
            reference: 'subscription-ref-1',
            status: 'OK',
        );

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->with($payment, $callback)
            ->andReturn(new PaymentGatewayResult(
                successful: true,
                transactionId: 'subscription-transaction-1',
            ));

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        $result = app(PaymentProcessingService::class)
            ->process($payment, $callback);

        $this->assertSame(
            PaymentStatus::PAID,
            $result->status
        );

        $this->assertSame(
            'subscription-transaction-1',
            $result->gateway_transaction_id
        );

        $this->assertNotNull($result->paid_at);

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

        $callback = new PaymentGatewayCallback(
            reference: 'rollback-ref-1',
            status: 'OK',
        );

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->with($payment, $callback)
            ->andReturn(new PaymentGatewayResult(
                successful: true,
                transactionId: 'rollback-transaction-1',
            ));

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        $subscriptionCreationService = $this->mock(
            SubscriptionCreationService::class
        );

        $subscriptionCreationService
            ->shouldReceive('create')
            ->once()
            ->andThrow(new RuntimeException('Subscription creation failed.'));

        try {
            app(PaymentProcessingService::class)
                ->process($payment, $callback);

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
            'gateway_transaction_id' => null,
            'paid_at' => null,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PENDING->value,
        ]);

        $this->assertDatabaseMissing('subscriptions', [
            'order_id' => $order->id,
        ]);
    }

    public function test_successful_subscription_payment_does_not_grant_item_access(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 300000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 300000,
        ]);

        $callback = new PaymentGatewayCallback(
            reference: 'subscription-access-ref-1',
            status: 'OK',
        );

        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->with($payment, $callback)
            ->andReturn(new PaymentGatewayResult(
                successful: true,
                transactionId: 'subscription-access-transaction-1',
            ));

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        app(PaymentProcessingService::class)
            ->process($payment, $callback);

        $this->assertDatabaseMissing('accesses', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        Carbon::setTestNow();
    }
}
