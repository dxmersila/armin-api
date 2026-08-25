<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
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
            'status' => $this->status->value,
            'total_price' => $this->total_price,

            'user' => new UserResource(
                $this->whenLoaded('user')
            ),

            'items' => OrderItemResource::collection(
                $this->whenLoaded('orderItems')
            ),

            'payments' => PaymentResource::collection(
                $this->whenLoaded('payments')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
