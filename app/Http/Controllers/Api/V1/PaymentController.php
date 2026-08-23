<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentCreationService;
use App\Services\PaymentProcessingService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function store(
        Order $order,
        PaymentCreationService $service
    ): JsonResponse {
        abort_unless(
            $order->user_id === request()->user()->id,
            403
        );

        $payment = $service->create($order);

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    public function process(
        Payment $payment,
        PaymentProcessingService $service
    ): PaymentResource {
        abort_unless(
            $payment->order->user_id === request()->user()->id,
            403
        );

        $payment = $service->process($payment);

        return new PaymentResource($payment);
    }
}
