<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class ItemAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_free_item(): void
    {
        $item = Item::factory()->create([
            'access_type' => 'free',
        ]);

        $response = $this->getJson("/api/v1/items/{$item->slug}/access");

        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_paid_item(): void
    {
        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $response = $this->getJson("/api/v1/items/{$item->slug}/access");

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_free_item(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'free',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/items/{$item->slug}/access");

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Item access granted.',
            ]);
    }

    public function test_authenticated_user_can_access_paid_item_with_individual_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        Access::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/items/{$item->slug}/access");

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Item access granted.',
            ]);
    }

    public function test_authenticated_user_cannot_access_paid_item_without_access(): void
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/items/{$item->slug}/access");

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_access_paid_item_with_active_subscription(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'order_id' => $order->id,
            'starts_at' => '2026-08-27 12:00:00',
            'ends_at' => '2026-11-27 12:00:00',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/items/{$item->slug}/access");

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Item access granted.',
            ]);
    }

    public function test_expired_subscription_does_not_grant_paid_item_access(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 1,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'order_id' => $order->id,
            'starts_at' => '2026-07-01 12:00:00',
            'ends_at' => '2026-08-01 12:00:00',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/v1/items/{$item->slug}/access");

        $response->assertForbidden();
    }
}
