<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_plan_can_be_created(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'name' => '1 Month',
            'duration_months' => 1,
            'price' => 100000,
        ]);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => '1 Month',
            'duration_months' => 1,
            'price' => 100000,
        ]);
    }

    public function test_subscription_plan_has_correct_duration(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
        ]);

        $this->assertEquals(3, $plan->duration_months);
    }

    public function test_subscription_plan_price_is_integer(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'price' => 500000,
        ]);

        $this->assertIsInt($plan->price);
    }

    public function test_subscription_plan_is_active_is_boolean(): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true,
        ]);

        $this->assertIsBool($plan->is_active);
        $this->assertTrue($plan->is_active);
    }

    public function test_subscription_plan_is_inactive_by_default(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $this->assertFalse($plan->is_active);
    }

    public function test_public_subscription_plans_returns_only_active_plans(): void
    {
        SubscriptionPlan::factory()->create([
            'name' => 'Inactive Plan',
            'duration_months' => 1,
            'price' => 50000,
            'is_active' => false,
        ]);

        $activePlan = SubscriptionPlan::factory()->create([
            'name' => 'Monthly',
            'duration_months' => 1,
            'price' => 99000,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/subscription-plans');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activePlan->id)
            ->assertJsonPath('data.0.name', 'Monthly')
            ->assertJsonPath('data.0.duration_months', 1)
            ->assertJsonPath('data.0.price', 99000)
            ->assertJsonPath('data.0.is_active', true);
    }
}
