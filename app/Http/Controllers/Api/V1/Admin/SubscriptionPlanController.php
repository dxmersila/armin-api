<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionPlanRequest;
use App\Http\Requests\UpdateSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index(): AnonymousResourceCollection
    {
        $plans = SubscriptionPlan::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return SubscriptionPlanResource::collection($plans);
    }

    /**
     * Store a newly created subscription plan.
     */
    public function store(
        StoreSubscriptionPlanRequest $request
    ): SubscriptionPlanResource {
        $plan = SubscriptionPlan::create(
            $request->validated()
        );

        return new SubscriptionPlanResource($plan);
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(
        SubscriptionPlan $subscriptionPlan
    ): SubscriptionPlanResource {
        return new SubscriptionPlanResource($subscriptionPlan);
    }

    /**
     * Update the specified subscription plan.
     */
    public function update(
        UpdateSubscriptionPlanRequest $request,
        SubscriptionPlan $subscriptionPlan
    ): SubscriptionPlanResource {
        $subscriptionPlan->update(
            $request->validated()
        );

        return new SubscriptionPlanResource($subscriptionPlan);
    }
}
