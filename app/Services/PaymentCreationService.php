<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
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

        if ($order->orderItems()->exists()) {
            $order->load('orderItems.item');

            foreach ($order->orderItems as $orderItem) {
                if (! $orderItem->item || ! $orderItem->item->is_visible) {
                    $orderItem->delete();
                }
            }

            app(OrderPricingService::class)->update($order);
            $order->refresh();
        }

        if ($order->total_price <= 0) {
            $order->update([
                'status' => OrderStatus::CANCELLED,
            ]);

            throw new InvalidArgumentException(
                'Order has no available items to purchase.'
            );
        }

        return DB::transaction(function () use ($order) {
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
        });
    }
}
