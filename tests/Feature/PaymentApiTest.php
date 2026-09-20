<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Contracts\Payments\PaymentGatewayCallback;
use App\Contracts\Payments\PaymentGatewayResult;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PaymentGatewayResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    public function test_creating_payment_removes_hidden_items_and_recalculates_order_total(): void
    {
        $user = User::factory()->create();

        $visibleItem = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 200000,
            'is_visible' => true,
        ]);

        $hiddenItem = Item::factory()->create([
            'access_type' => 'paid',
            'price' => 100000,
            'is_visible' => false,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 300000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $visibleItem->id,
            'price' => 200000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $hiddenItem->id,
            'price' => 100000,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/orders/{$order->id}/payments");

        $response
            ->assertCreated()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.amount', 200000)
            ->assertJsonPath('data.status', PaymentStatus::PENDING->value);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 200000,
            'status' => PaymentStatus::PENDING->value,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'item_id' => $visibleItem->id,
        ]);

        $this->assertDatabaseMissing('order_items', [
            'order_id' => $order->id,
            'item_id' => $hiddenItem->id,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'total_price' => 200000,
            'status' => OrderStatus::PENDING->value,
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
            ->assertJsonPath(
                'data.type',
                OrderType::SUBSCRIPTION->value
            )
            ->assertJsonPath(
                'data.status',
                OrderStatus::PENDING->value
            )
            ->assertJsonPath(
                'data.total_price',
                300000
            );

        $orderId = $orderResponse->json('data.id');

        // 2. Create payment for the pending order.
        $paymentResponse = $this->postJson(
            "/api/v1/orders/{$orderId}/payments"
        );

        $paymentResponse
            ->assertCreated()
            ->assertJsonPath(
                'data.order_id',
                $orderId
            )
            ->assertJsonPath(
                'data.status',
                PaymentStatus::PENDING->value
            )
            ->assertJsonPath(
                'data.amount',
                300000
            );

        $paymentId = $paymentResponse->json('data.id');

        // 3. Configure the fake gateway.
        $gateway = $this->mock(PaymentGateway::class);

        $gateway
            ->shouldReceive('name')
            ->twice()
            ->andReturn('fake');

        $gateway
            ->shouldReceive('initiate')
            ->once()
            ->withArgs(function (Payment $payment) use ($paymentId) {
                return $payment->id === $paymentId;
            })
            ->andReturn(
                new PaymentInitiationResult(
                    redirectUrl: 'https://gateway.test/pay/' . $paymentId,
                    reference: 'ref-' . $paymentId,
                )
            );

        $callback = new PaymentGatewayCallback(
            reference: 'ref-' . $paymentId,
            status: 'OK',
        );

        $gateway
            ->shouldReceive('verify')
            ->once()
            ->withArgs(function (
                Payment $payment,
                PaymentGatewayCallback $receivedCallback
            ) use ($paymentId, $callback) {
                return $payment->id === $paymentId
                    && $receivedCallback->reference === $callback->reference
                    && $receivedCallback->status === $callback->status;
            })
            ->andReturn(
                new PaymentGatewayResult(
                    successful: true,
                    transactionId: 'test-transaction-1',
                )
            );

        $this->mock(
            PaymentGatewayResolver::class,
            function ($resolver) use ($gateway) {
                $resolver
                    ->shouldReceive('resolve')
                    ->once()
                    ->andReturn($gateway);

                $resolver
                    ->shouldReceive('resolveByName')
                    ->once()
                    ->with('fake')
                    ->andReturn($gateway);
            }
        );

        // 4. Initiate payment.
        $initiateResponse = $this->postJson(
            "/api/v1/payments/{$paymentId}/initiate"
        );

        $initiateResponse
            ->assertOk()
            ->assertJsonPath(
                'data.payment_id',
                $paymentId
            )
            ->assertJsonPath(
                'data.redirect_url',
                'https://gateway.test/pay/' . $paymentId
            )
            ->assertJsonPath(
                'data.reference',
                'ref-' . $paymentId
            );

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => PaymentStatus::PENDING->value,
            'gateway' => 'fake',
            'gateway_reference' => 'ref-' . $paymentId,
        ]);

        // 5. Simulate the gateway callback.
        config([
            'payment.web_app_url' => 'http://web.test',
        ]);

        $callbackResponse = $this->get(
            "/api/v1/payments/{$paymentId}/callback"
                . '?Authority=ref-' . $paymentId
                . '&Status=OK'
        );

        $callbackResponse->assertRedirect(
            'http://web.test/payment/result?order_id='
                . $orderId
                . '&payment_id='
                . $paymentId
                . '&status=paid'
        );

        // 6. Verify the order is paid.
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $plan->id,
            'status' => OrderStatus::PAID->value,
            'total_price' => 300000,
        ]);

        // 7. Verify the subscription was created.
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

        Carbon::setTestNow();
    }
}
