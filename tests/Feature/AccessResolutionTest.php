<?php

namespace Tests\Feature;

use App\Models\Access;
use App\Models\Item;
use App\Models\User;
use App\Services\AccessResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Enums\OrderType;
use Carbon\Carbon;

class AccessResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_free_item(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'access_type' => 'free',
        ]);

        $resolver = app(AccessResolver::class);

        $this->assertTrue(
            $resolver->canAccess($user, $item)
        );
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

        $resolver = app(AccessResolver::class);

        $this->assertTrue(
            $resolver->canAccess($user, $item)
        );
    }

    public function test_authenticated_user_cannot_access_paid_item_without_access(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $resolver = app(AccessResolver::class);

        $this->assertFalse(
            $resolver->canAccess($user, $item)
        );
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

        $resolver = app(AccessResolver::class);

        $this->assertTrue(
            $resolver->canAccess($user, $item)
        );
    }

    public function test_authenticated_user_cannot_access_paid_item_with_expired_subscription(): void
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

        $resolver = app(AccessResolver::class);

        $this->assertFalse(
            $resolver->canAccess($user, $item)
        );
    }

    public function test_subscription_gives_access_to_all_paid_items(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $firstItem = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $secondItem = Item::factory()->create([
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

        $resolver = app(AccessResolver::class);

        $this->assertTrue(
            $resolver->canAccess($user, $firstItem)
        );

        $this->assertTrue(
            $resolver->canAccess($user, $secondItem)
        );
    }

    public function test_expired_subscription_does_not_revoke_direct_access(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $item = Item::factory()->create([
            'access_type' => 'paid',
        ]);

        $user->accesses()->create([
            'item_id' => $item->id,
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

        $resolver = app(AccessResolver::class);

        $this->assertTrue(
            $resolver->canAccess($user, $item)
        );
    }

    public function test_future_subscription_does_not_grant_access(): void
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
            'starts_at' => '2026-09-01 12:00:00',
            'ends_at' => '2026-12-01 12:00:00',
        ]);

        $resolver = app(AccessResolver::class);

        $this->assertFalse(
            $resolver->canAccess($user, $item)
        );
    }

    public function test_subscription_is_inactive_at_exact_end_time(): void
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
            'starts_at' => '2026-07-27 12:00:00',
            'ends_at' => '2026-08-27 12:00:00',
        ]);

        $resolver = app(AccessResolver::class);

        $this->assertFalse(
            $resolver->canAccess($user, $item)
        );
    }
}
