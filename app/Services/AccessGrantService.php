<?php

namespace App\Services;

use App\Models\Access;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;

class AccessGrantService
{
    public function grant(User $user, Item $item): void
    {
        Access::firstOrCreate([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }

    public function grantForOrder(Order $order): void
    {
        $order->orderItems()
            ->get()
            ->each(function ($orderItem) use ($order) {
                $this->grant(
                    $order->user,
                    $orderItem->item
                );
            });
    }
}
