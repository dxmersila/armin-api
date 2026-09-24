<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionPlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('duration_months')
            ->orderBy('id')
            ->get();

        return SubscriptionPlanResource::collection($plans);
    }
}
