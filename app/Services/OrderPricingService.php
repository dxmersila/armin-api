<?php

namespace App\Services;

use App\Models\Order;

class OrderPricingService
{
    public function calculate(Order $order): int
    {
        return (int) $order->orderItems()->sum('price');
    }

    public function update(Order $order): int
    {
        $totalPrice = $this->calculate($order);

        $order->update([
            'total_price' => $totalPrice,
        ]);

        return $totalPrice;
    }
}
