<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_subscription_plans(): void
    {
        $response = $this->getJson('/api/v1/admin/subscription-plans');

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_list_subscription_plans(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/admin/subscription-plans');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_subscription_plans(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        SubscriptionPlan::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->getJson('/api/v1/admin/subscription-plans');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'duration_months',
                        'price',
                        'is_active',
                    ],
                ],
            ]);
    }

    public function test_admin_can_create_subscription_plan(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson('/api/v1/admin/subscription-plans', [
                'name' => '3 Months',
                'duration_months' => 3,
                'price' => 300000,
                'is_active' => true,
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.name', '3 Months')
            ->assertJsonPath('data.duration_months', 3)
            ->assertJsonPath('data.price', 300000)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('subscription_plans', [
            'name' => '3 Months',
            'duration_months' => 3,
            'price' => 300000,
        ]);
    }

    public function test_admin_can_view_subscription_plan(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $plan = SubscriptionPlan::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->getJson("/api/v1/admin/subscription-plans/{$plan->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $plan->id);
    }

    public function test_admin_can_update_subscription_plan(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patchJson("/api/v1/admin/subscription-plans/{$plan->id}", [
                'is_active' => false,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_subscription_plans_are_deterministically_ordered_by_id_when_created_at_matches(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $createdAt = now();

        $firstPlan = SubscriptionPlan::factory()->create([
            'created_at' => $createdAt,
        ]);

        $secondPlan = SubscriptionPlan::factory()->create([
            'created_at' => $createdAt,
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson('/api/v1/admin/subscription-plans');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $secondPlan->id)
            ->assertJsonPath('data.1.id', $firstPlan->id);
    }
}
