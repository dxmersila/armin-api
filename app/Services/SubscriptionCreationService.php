<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SubscriptionCreationService
{
    public function create(
        User $user,
        SubscriptionPlan $plan,
        Order $order,
    ): Subscription {
        $now = Carbon::now();

        $activeSubscriptionExists = $user->subscriptions()
            ->where('ends_at', '>', $now)
            ->exists();

        if ($activeSubscriptionExists) {
            throw ValidationException::withMessages([
                'subscription' => 'User already has an active subscription.',
            ]);
        }

        $startsAt = $now;

        $endsAt = $startsAt->copy()->addMonthsNoOverflow(
            $plan->duration_months
        );

        return $user->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'order_id' => $order->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
