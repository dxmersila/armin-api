<?php

namespace App\Services;

use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SubscriptionCreationService
{
    public function create(
        User $user,
        SubscriptionPlan $plan,
        Order $order,
    ): Subscription {
        if ($order->type !== OrderType::SUBSCRIPTION) {
            throw new InvalidArgumentException(
                'Subscription can only be created for a subscription order.'
            );
        }

        if ($order->user_id !== $user->id) {
            throw new InvalidArgumentException(
                'Subscription order must belong to the given user.'
            );
        }

        if ($order->subscription_plan_id !== $plan->id) {
            throw new InvalidArgumentException(
                'Subscription plan must match the order subscription plan.'
            );
        }

        $now = Carbon::now();

        $activeSubscriptionExists = $user->subscriptions()
            ->where('starts_at', '<=', $now)
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
            'price' => $plan->price,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
