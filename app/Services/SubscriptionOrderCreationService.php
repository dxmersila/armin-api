<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SubscriptionOrderCreationService
{
    public function create(
        User $user,
        SubscriptionPlan $plan,
    ): Order {
        if (! $plan->is_active) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'Subscription plan is not available.',
            ]);
        }

        $activeSubscriptionExists = $user->subscriptions()
            ->where('ends_at', '>', Carbon::now())
            ->exists();

        if ($activeSubscriptionExists) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'User already has an active subscription.',
            ]);
        }

        return DB::transaction(function () use ($user, $plan) {
            return Order::create([
                'user_id' => $user->id,
                'type' => OrderType::SUBSCRIPTION,
                'status' => OrderStatus::PENDING,
                'subscription_plan_id' => $plan->id,
                'total_price' => $plan->price,
            ]);
        });
    }
}
