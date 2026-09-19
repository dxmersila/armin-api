<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class OrderCancellationService
{
    public function cancel(Order $order): void
    {
        if ($order->status !== OrderStatus::PENDING) {
            throw ValidationException::withMessages([
                'order' => 'Only pending orders can be cancelled.',
            ]);
        }

        $order->update([
            'status' => OrderStatus::CANCELLED,
        ]);
    }
}
