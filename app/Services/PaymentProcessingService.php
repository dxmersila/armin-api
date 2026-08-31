<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
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
            $result = $this->gateway->pay($payment);

            if ($result->successful) {
                $payment->update([
                    'status' => PaymentStatus::PAID,
                    'gateway' => $this->gateway->name(),
                    'gateway_transaction_id' => $result->transactionId,
                    'paid_at' => now(),
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
                } else {
                    $this->accessGrantService->grantForOrder($order);
                }
            } else {
                $payment->update([
                    'status' => PaymentStatus::FAILED,
                    'gateway' => $this->gateway->name(),
                ]);
            }

            return $payment->fresh();
        });
    }
}
