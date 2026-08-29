<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'order_id' => Order::factory(),
            'price' => 100000,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ];
    }
}
