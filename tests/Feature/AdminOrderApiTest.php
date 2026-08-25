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
}
