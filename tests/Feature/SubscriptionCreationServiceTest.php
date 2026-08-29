<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class SubscriptionCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_subscription_from_now_when_user_has_no_active_subscription(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        $service = app(SubscriptionCreationService::class);

        $subscription = $service->create(
            user: $user,
            plan: $plan,
            order: $order,
        );

        $this->assertEquals(
            Carbon::parse('2026-08-26 12:00:00'),
            $subscription->starts_at
        );

        $this->assertEquals(
            Carbon::parse('2026-11-26 12:00:00'),
            $subscription->ends_at
        );
    }

    public function test_it_rejects_creation_when_user_has_active_subscription(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'subscription_plan_id' => $plan->id,
            'price' => 300000,
            'starts_at' => '2026-08-01 12:00:00',
            'ends_at' => '2026-11-01 12:00:00',
        ]);

        $service = app(SubscriptionCreationService::class);

        $this->expectException(ValidationException::class);

        $service->create(
            user: $user,
            plan: $plan,
            order: $order,
        );
    }

    public function test_it_ignores_expired_subscription_when_calculating_start_date(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 1,
            'price' => 100000,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'starts_at' => '2026-07-01 12:00:00',
            'ends_at' => '2026-08-01 12:00:00',
            'price' => 100000,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        $service = app(SubscriptionCreationService::class);

        $subscription = $service->create(
            user: $user,
            plan: $plan,
            order: $order,
        );

        $this->assertEquals(
            Carbon::parse('2026-08-26 12:00:00'),
            $subscription->starts_at
        );

        $this->assertEquals(
            Carbon::parse('2026-09-26 12:00:00'),
            $subscription->ends_at
        );
    }

    public function test_it_calculates_end_date_without_month_overflow(): void
    {
        Carbon::setTestNow('2026-01-31 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 1,
            'price' => 100000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        $service = app(SubscriptionCreationService::class);

        $subscription = $service->create(
            user: $user,
            plan: $plan,
            order: $order,
        );

        $this->assertEquals(
            Carbon::parse('2026-01-31 12:00:00'),
            $subscription->starts_at
        );

        $this->assertEquals(
            Carbon::parse('2026-02-28 12:00:00'),
            $subscription->ends_at
        );
    }

    public function test_subscription_price_is_snapshot_of_plan_price(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        $service = app(SubscriptionCreationService::class);

        $subscription = $service->create(
            user: $user,
            plan: $plan,
            order: $order,
        );

        $plan->update([
            'price' => 500000,
        ]);

        $subscription->refresh();

        $this->assertSame(300000, $subscription->price);
    }

    public function test_future_subscription_does_not_block_new_subscription_creation(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $currentPlan = SubscriptionPlan::factory()->create([
            'duration_months' => 1,
            'price' => 100000,
            'is_active' => true,
        ]);

        $newPlan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $existingOrder = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $currentPlan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $currentPlan->id,
            'order_id' => $existingOrder->id,
            'price' => 100000,
            'starts_at' => '2026-09-01 12:00:00',
            'ends_at' => '2026-10-01 12:00:00',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $newPlan->id,
        ]);

        $subscription = app(SubscriptionCreationService::class)->create(
            user: $user,
            plan: $newPlan,
            order: $order,
        );

        $this->assertEquals(
            Carbon::parse('2026-08-27 12:00:00'),
            $subscription->starts_at
        );
    }

    public function test_subscription_ending_now_does_not_block_new_subscription_creation(): void
    {
        Carbon::setTestNow('2026-08-27 12:00:00');

        $user = User::factory()->create();

        $currentPlan = SubscriptionPlan::factory()->create([
            'duration_months' => 1,
            'price' => 100000,
            'is_active' => true,
        ]);

        $newPlan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $existingOrder = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $currentPlan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $currentPlan->id,
            'order_id' => $existingOrder->id,
            'price' => 100000,
            'starts_at' => '2026-07-27 12:00:00',
            'ends_at' => '2026-08-27 12:00:00',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $newPlan->id,
        ]);

        $subscription = app(SubscriptionCreationService::class)->create(
            user: $user,
            plan: $newPlan,
            order: $order,
        );

        $this->assertEquals(
            Carbon::parse('2026-08-27 12:00:00'),
            $subscription->starts_at
        );
    }

    public function test_it_rejects_non_subscription_order(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::ITEM,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(SubscriptionCreationService::class)->create(
            user: $user,
            plan: $plan,
            order: $order,
        );
    }

    public function test_it_rejects_order_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $anotherUser->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(SubscriptionCreationService::class)->create(
            user: $user,
            plan: $plan,
            order: $order,
        );
    }

    public function test_it_rejects_plan_that_does_not_match_order(): void
    {
        $user = User::factory()->create();

        $orderPlan = SubscriptionPlan::factory()->create([
            'duration_months' => 1,
            'price' => 100000,
            'is_active' => true,
        ]);

        $servicePlan = SubscriptionPlan::factory()->create([
            'duration_months' => 3,
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $orderPlan->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(SubscriptionCreationService::class)->create(
            user: $user,
            plan: $servicePlan,
            order: $order,
        );
    }
}
