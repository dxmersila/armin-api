<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Contracts\Payments\PaymentGatewayResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Contracts\PaymentGateway;
use App\Enums\OrderType;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaymentGatewayResolver;
use Carbon\Carbon;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_payment_for_own_pending_order(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 150000,
            'is_visible' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => 150000,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments");

        $response
            ->assertCreated()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.amount', 150000)
            ->assertJsonPath('data.status', PaymentStatus::PENDING->value);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 150000,
            'status' => PaymentStatus::PENDING->value,
        ]);
    }

    public function test_user_cannot_create_payment_for_another_users_order(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $anotherUser->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments")
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_cannot_be_created_for_non_pending_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PAID,
            'total_price' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments")
            ->assertUnprocessable();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_cannot_be_created_for_zero_price_order(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 0,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments")
            ->assertUnprocessable();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_guest_cannot_create_payment(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $this->postJson("/api/v1/orders/{$order->id}/payments")
            ->assertUnauthorized();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_authenticated_user_can_process_own_pending_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/process")
            ->assertOk()
            ->assertJsonPath('data.id', $payment->id)
            ->assertJsonPath('data.status', PaymentStatus::PAID->value);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PAID->value,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PAID->value,
        ]);
    }

    public function test_user_cannot_process_another_users_payment(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $anotherUser->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/process")
            ->assertForbidden();
    }

    public function test_guest_cannot_process_payment(): void
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

        $this->postJson("/api/v1/payments/{$payment->id}/process")
            ->assertUnauthorized();
    }

    public function test_paid_payment_cannot_be_processed_again(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PAID,
            'total_price' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PAID,
            'amount' => 150000,
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/payments/{$payment->id}/process")
            ->assertUnprocessable();
    }

    public function test_user_can_complete_subscription_purchase_flow(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'name' => '3 Months',
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // 1. Create subscription order.
        $orderResponse = $this->postJson('/api/v1/orders', [
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $plan->id,
        ]);

        $orderResponse
            ->assertCreated()
            ->assertJsonPath('data.type', OrderType::SUBSCRIPTION->value)
            ->assertJsonPath('data.status', OrderStatus::PENDING->value)
            ->assertJsonPath('data.total_price', 300000);

        $orderId = $orderResponse->json('data.id');

        // 2. Create payment for the pending order.
        $paymentResponse = $this->postJson(
            "/api/v1/orders/{$orderId}/payments"
        );

        $paymentResponse
            ->assertCreated()
            ->assertJsonPath('data.order_id', $orderId)
            ->assertJsonPath(
                'data.status',
                PaymentStatus::PENDING->value
            )
            ->assertJsonPath('data.amount', 300000);

        $paymentId = $paymentResponse->json('data.id');

        // 3. Force the gateway to simulate a successful payment.
        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->once()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('pay')
            ->once()
            ->andReturn(
                new PaymentGatewayResult(
                    successful: true,
                    transactionId: 'test-transaction-1',
                )
            );

        $this->mock(PaymentGatewayResolver::class, function ($resolver) use ($gateway) {
            $resolver
                ->shouldReceive('resolve')
                ->once()
                ->andReturn($gateway);
        });

        // 4. Process the payment.
        $processResponse = $this->postJson(
            "/api/v1/payments/{$paymentId}/process"
        );

        $processResponse
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                PaymentStatus::PAID->value
            );

        // 5. Verify the order is paid.
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $plan->id,
            'status' => OrderStatus::PAID->value,
            'total_price' => 300000,
        ]);

        // 6. Verify the subscription was created.
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'order_id' => $orderId,
            'starts_at' => '2026-08-27 12:00:00',
            'ends_at' => '2026-11-27 12:00:00',
        ]);

        $this->assertTrue(
            Subscription::where('order_id', $orderId)->exists()
        );
    }
}
