<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionOrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\OrderStatus;
use App\Models\Order;
use Tests\TestCase;
use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionOrderCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_subscription_order(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = app(SubscriptionOrderCreationService::class)
            ->create($user, $plan);

        $this->assertSame(
            OrderType::SUBSCRIPTION,
            $order->type
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION->value,
            'subscription_plan_id' => $plan->id,
            'total_price' => 300000,
        ]);
    }

    public function test_it_rejects_inactive_subscription_plan(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'is_active' => false,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(SubscriptionOrderCreationService::class)
            ->create($user, $plan);
    }

    public function test_it_creates_subscription_order_with_pending_status(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'price' => 500000,
            'is_active' => true,
        ]);

        $order = app(SubscriptionOrderCreationService::class)
            ->create($user, $plan);

        $this->assertSame(
            OrderStatus::PENDING,
            $order->status
        );
    }

    public function test_subscription_order_uses_plan_price(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'price' => 750000,
            'is_active' => true,
        ]);

        $order = app(SubscriptionOrderCreationService::class)
            ->create($user, $plan);

        $this->assertSame(750000, $order->total_price);
    }

    public function test_subscription_order_references_selected_plan(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true,
        ]);

        $order = app(SubscriptionOrderCreationService::class)
            ->create($user, $plan);

        $this->assertSame(
            $plan->id,
            $order->subscription_plan_id
        );
    }

    public function test_it_rejects_order_creation_when_user_has_active_subscription(): void
    {
        Carbon::setTestNow('2026-08-26 12:00:00');

        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => OrderType::SUBSCRIPTION,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'starts_at' => '2026-08-01 12:00:00',
            'ends_at' => '2026-11-01 12:00:00',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(SubscriptionOrderCreationService::class)
            ->create($user, $plan);
    }

    public function test_subscription_order_price_is_snapshot_of_plan_price(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create([
            'price' => 300000,
            'is_active' => true,
        ]);

        $order = app(SubscriptionOrderCreationService::class)
            ->create($user, $plan);

        $plan->update([
            'price' => 500000,
        ]);

        $this->assertSame(
            300000,
            $order->total_price
        );

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'total_price' => 300000,
        ]);
    }
}
