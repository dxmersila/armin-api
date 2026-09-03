<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\SubscriptionPlan;

class AdminOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_orders(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Order::factory()->count(3)->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'status',
                        'total_price',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        $response->assertJsonCount(3, 'data');
    }

    public function test_guest_cannot_list_orders(): void
    {
        $this->getJson('/api/v1/admin/orders')
            ->assertUnauthorized();
    }

    public function test_regular_user_cannot_list_orders(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/admin/orders')
            ->assertForbidden();
    }

    public function test_admin_order_list_returns_newest_orders_first(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $oldOrder = Order::factory()->create([
            'created_at' => now()->subDays(2),
            'status' => OrderStatus::PENDING,
            'total_price' => 100000,
        ]);

        $newOrder = Order::factory()->create([
            'created_at' => now(),
            'status' => OrderStatus::PENDING,
            'total_price' => 200000,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $newOrder->id)
            ->assertJsonPath('data.1.id', $oldOrder->id);
    }

    public function test_admin_order_list_is_paginated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Order::factory()->count(20)->create([
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonCount(15, 'data');
    }

    public function test_admin_order_list_includes_order_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'type' => \App\Enums\OrderType::SUBSCRIPTION,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/orders');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $order->id
            )
            ->assertJsonPath(
                'data.0.type',
                \App\Enums\OrderType::SUBSCRIPTION->value
            );
    }

    public function test_admin_can_view_order_details(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        $item = \App\Models\Item::factory()->create([
            'access_type' => 'paid',
            'price' => 150000,
        ]);

        $orderItem = \App\Models\OrderItem::factory()->create([
            'order_id' => $order->id,
            'item_id' => $item->id,
            'price' => 150000,
        ]);

        $payment = \App\Models\Payment::factory()->create([
            'order_id' => $order->id,
            'status' => \App\Enums\PaymentStatus::PENDING,
            'amount' => 150000,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.status', OrderStatus::PENDING->value)
            ->assertJsonPath('data.total_price', 150000);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'status',
                'total_price',
                'created_at',
                'updated_at',
                'user',
                'items',
                'payments',
            ],
        ]);
    }

    public function test_guest_cannot_view_order_details(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/admin/orders/{$order->id}")
            ->assertUnauthorized();
    }

    public function test_regular_user_cannot_view_order_details(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $order = Order::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/admin/orders/{$order->id}")
            ->assertForbidden();
    }

    public function test_admin_cannot_view_non_existing_order(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders/999999')
            ->assertNotFound();
    }

    public function test_admin_can_filter_orders_by_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $pendingOrder = Order::factory()->create([
            'status' => OrderStatus::PENDING,
        ]);

        $paidOrder = Order::factory()->create([
            'status' => OrderStatus::PAID,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders?status=pending');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendingOrder->id)
            ->assertJsonPath(
                'data.0.status',
                OrderStatus::PENDING->value
            );

        $response->assertJsonMissing([
            'id' => $paidOrder->id,
        ]);
    }

    public function test_admin_order_list_rejects_invalid_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders?status=something-invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_admin_can_filter_orders_by_user_id(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $userOrder = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
        ]);

        Order::factory()->create([
            'user_id' => $anotherUser->id,
            'status' => OrderStatus::PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/v1/admin/orders?user_id={$user->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $userOrder->id)
            ->assertJsonPath('data.0.user_id', $user->id);
    }

    public function test_admin_order_list_rejects_invalid_user_id(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders?user_id=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_admin_can_view_order_payments_in_order_details(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
            'total_price' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 150000,
            'status' => PaymentStatus::PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/v1/admin/orders/{$order->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.payments')
            ->assertJsonPath(
                'data.payments.0.order_id',
                $order->id
            )
            ->assertJsonPath(
                'data.payments.0.amount',
                150000
            )
            ->assertJsonPath(
                'data.payments.0.status',
                PaymentStatus::PENDING->value
            );
    }

    public function test_admin_can_filter_orders_by_payment_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $paidOrder = Order::factory()->create();

        Payment::factory()->create([
            'order_id' => $paidOrder->id,
            'status' => PaymentStatus::PAID,
        ]);

        $pendingOrder = Order::factory()->create();

        Payment::factory()->create([
            'order_id' => $pendingOrder->id,
            'status' => PaymentStatus::PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders?payment_status=paid');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $paidOrder->id
            )
            ->assertJsonPath(
                'data.0.status',
                $paidOrder->status->value
            );
    }

    public function test_admin_order_list_rejects_invalid_payment_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/orders?payment_status=something-invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_status']);
    }

    public function test_admin_can_view_subscription_plan_in_order_details(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'name' => '6 Months',
            'duration_months' => 6,
            'price' => 500000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'type' => \App\Enums\OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
            'total_price' => 500000,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(
            "/api/v1/admin/orders/{$order->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.type',
                \App\Enums\OrderType::SUBSCRIPTION->value
            )
            ->assertJsonPath(
                'data.subscription_plan.id',
                $plan->id
            )
            ->assertJsonPath(
                'data.subscription_plan.name',
                '6 Months'
            )
            ->assertJsonPath(
                'data.subscription_plan.duration_months',
                6
            )
            ->assertJsonPath(
                'data.subscription_plan.price',
                500000
            );
    }

    public function test_admin_order_list_is_deterministically_ordered_by_id_when_created_at_matches(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $createdAt = now();

        $firstOrder = Order::factory()->create([
            'created_at' => $createdAt,
        ]);

        $secondOrder = Order::factory()->create([
            'created_at' => $createdAt,
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson('/api/v1/admin/orders');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $secondOrder->id)
            ->assertJsonPath('data.1.id', $firstOrder->id);
    }

    public function test_admin_payment_status_filter_matches_any_payment_on_order(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create();

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::FAILED,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status' => PaymentStatus::PAID,
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson('/api/v1/admin/orders?payment_status=paid');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $order->id);
    }
}
