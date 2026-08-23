<?php

namespace App\Services;

use App\Models\Access;
use App\Models\Order;

class AccessGrantService
{
    public function grantForOrder(Order $order): void
    {
        $order->orderItems()
            ->get()
            ->each(function ($orderItem) use ($order) {
                Access::firstOrCreate([
                    'user_id' => $order->user_id,
                    'item_id' => $orderItem->item_id,
                ]);
            });
    }
}
