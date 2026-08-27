<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_can_be_created(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    public function test_subscription_belongs_to_user(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(
            User::class,
            $subscription->user
        );

        $this->assertEquals(
            $subscription->user_id,
            $subscription->user->id
        );
    }

    public function test_subscription_belongs_to_plan(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(
            SubscriptionPlan::class,
            $subscription->subscriptionPlan
        );

        $this->assertEquals(
            $subscription->subscription_plan_id,
            $subscription->subscriptionPlan->id
        );
    }

    public function test_subscription_belongs_to_order(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(
            Order::class,
            $subscription->order
        );

        $this->assertEquals(
            $subscription->order_id,
            $subscription->order->id
        );
    }

    public function test_subscription_dates_are_cast_to_datetime(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $subscription->starts_at
        );

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $subscription->ends_at
        );
    }

    public function test_user_has_many_subscriptions(): void
    {
        $user = User::factory()->create();

        Subscription::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $user->subscriptions);
    }

    public function test_subscription_plan_has_many_subscriptions(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        Subscription::factory()->count(2)->create([
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertCount(2, $plan->subscriptions);
    }

    public function test_order_has_one_subscription(): void
    {
        $order = Order::factory()->create([
            'type' => \App\Enums\OrderType::SUBSCRIPTION,
        ]);

        $subscription = Subscription::factory()->create([
            'order_id' => $order->id,
        ]);

        $this->assertTrue(
            $order->subscription->is($subscription)
        );
    }

    public function test_order_cannot_have_multiple_subscriptions(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'type' => \App\Enums\OrderType::SUBSCRIPTION,
            'subscription_plan_id' => $plan->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'order_id' => $order->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'order_id' => $order->id,
        ]);
    }
}
