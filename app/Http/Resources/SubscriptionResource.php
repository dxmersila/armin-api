<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,

            'subscription_plan' => new SubscriptionPlanResource(
                $this->whenLoaded('subscriptionPlan')
            ),

            'price' => $this->price,

            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,

            'is_active' => $this->starts_at !== null
                && $this->ends_at !== null
                && $this->starts_at->lte(now())
                && $this->ends_at->gt(now()),

            'created_at' => $this->created_at,
        ];
    }
}
