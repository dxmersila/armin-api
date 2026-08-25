<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
}
