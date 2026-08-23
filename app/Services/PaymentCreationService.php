<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use InvalidArgumentException;

class PaymentCreationService
{
    public function create(Order $order): Payment
    {
        if ($order->status !== OrderStatus::PENDING) {
            throw new InvalidArgumentException(
                'Only pending orders can be paid.'
            );
        }

        if ($order->total_price <= 0) {
            throw new InvalidArgumentException(
                'Order total price must be greater than zero.'
            );
        }

        $hasPendingPayment = $order->payments()
            ->where('status', PaymentStatus::PENDING)
            ->exists();

        if ($hasPendingPayment) {
            throw new InvalidArgumentException(
                'This order already has a pending payment.'
            );
        }

        return $order->payments()->create([
            'status' => PaymentStatus::PENDING,
            'amount' => $order->total_price,
        ]);
    }
}
