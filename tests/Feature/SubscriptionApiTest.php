<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_subscriptions(): void
    {
        $response = $this->getJson('/api/v1/subscriptions');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_own_subscriptions(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'name' => '3 Months',
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $otherPlan = SubscriptionPlan::factory()->create([
            'name' => '1 Year',
            'duration_months' => 12,
            'price' => 1000000,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'price' => 300000,
            'starts_at' => '2026-08-27 12:00:00',
            'ends_at' => '2026-11-27 12:00:00',
        ]);

        Subscription::factory()->create([
            'user_id' => $otherUser->id,
            'subscription_plan_id' => $otherPlan->id,
            'price' => 1000000,
            'starts_at' => '2026-08-27 12:00:00',
            'ends_at' => '2027-08-27 12:00:00',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/subscriptions');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $subscription->id)
            ->assertJsonPath('data.0.user_id', $user->id)
            ->assertJsonPath('data.0.subscription_plan.id', $plan->id)
            ->assertJsonPath(
                'data.0.subscription_plan.name',
                '3 Months'
            )
            ->assertJsonPath(
                'data.0.subscription_plan.duration_months',
                3
            )
            ->assertJsonPath(
                'data.0.subscription_plan.price',
                300000
            )
            ->assertJsonPath(
                'data.0.price',
                300000
            )
            ->assertJsonPath(
                'data.0.starts_at',
                '2026-08-27T12:00:00.000000Z'
            )
            ->assertJsonPath(
                'data.0.ends_at',
                '2026-11-27T12:00:00.000000Z'
            )
            ->assertJsonPath('data.0.is_active', true);
    }

    public function test_expired_subscription_is_not_active(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 1,
            'price' => 100000,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'price' => 100000,
            'starts_at' => '2026-07-01 12:00:00',
            'ends_at' => '2026-08-01 12:00:00',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/subscriptions');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $subscription->id)
            ->assertJsonPath('data.0.price', 100000)
            ->assertJsonPath('data.0.is_active', false);
    }

    public function test_future_subscription_is_not_active(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'price' => 300000,
            'starts_at' => '2026-09-01 12:00:00',
            'ends_at' => '2026-12-01 12:00:00',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/subscriptions');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $subscription->id)
            ->assertJsonPath('data.0.price', 300000)
            ->assertJsonPath('data.0.is_active', false);
    }
}
