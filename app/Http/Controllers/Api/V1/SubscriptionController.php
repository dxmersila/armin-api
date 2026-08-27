<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $subscriptions = Subscription::query()
            ->where('user_id', $request->user()->id)
            ->with('subscriptionPlan')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate();

        return SubscriptionResource::collection($subscriptions);
    }
}
