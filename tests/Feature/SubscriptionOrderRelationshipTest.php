<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionOrderRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_belongs_to_subscription_plan(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        $order = Order::factory()->create([
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertTrue(
            $order->subscriptionPlan->is($plan)
        );
    }

    public function test_subscription_plan_has_many_orders(): void
    {
        $plan = SubscriptionPlan::factory()->create();

        Order::factory()->create([
            'subscription_plan_id' => $plan->id,
        ]);

        $this->assertCount(
            1,
            $plan->orders
        );
    }
}
