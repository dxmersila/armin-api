<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Order;
use App\Enums\OrderType;

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
            'type' => \App\Enums\OrderType::SUBSCRIPTION,
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
            'type' => \App\Enums\OrderType::SUBSCRIPTION,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'starts_at' => '2026-08-01 12:00:00',
            'ends_at' => '2026-11-01 12:00:00',
        ]);

        $service = app(SubscriptionCreationService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

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
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => \App\Enums\OrderType::SUBSCRIPTION,
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
}
