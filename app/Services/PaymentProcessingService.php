<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\SubscriptionCreationService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentProcessingService
{
    public function __construct(
        protected PaymentGateway $gateway,
        protected AccessGrantService $accessGrantService,
        protected SubscriptionCreationService $subscriptionCreationService,
    ) {}

    public function process(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::PENDING) {
            throw new InvalidArgumentException(
                'Only pending payments can be processed.'
            );
        }

        return DB::transaction(function () use ($payment) {
            $successful = $this->gateway->pay($payment);

            if ($successful) {
                $payment->update([
                    'status' => PaymentStatus::PAID,
                ]);

                $order = $payment->order;

                $order->update([
                    'status' => OrderStatus::PAID,
                ]);

                if ($order->type === OrderType::SUBSCRIPTION) {
                    $this->subscriptionCreationService->create(
                        $order->user,
                        $order->subscriptionPlan,
                        $order,
                    );
                }

                $this->accessGrantService->grantForOrder($order);
            } else {
                $payment->update([
                    'status' => PaymentStatus::FAILED,
                ]);
            }

            return $payment->fresh();
        });
    }
}
