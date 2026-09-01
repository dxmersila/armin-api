<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Payments\PaymentGatewayCallback;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentCreationService;
use App\Services\PaymentInitiationService;
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

    public function initiate(
        Payment $payment,
        PaymentInitiationService $service
    ): JsonResponse {
        abort_unless(
            $payment->order->user_id === request()->user()->id,
            403
        );

        $result = $service->initiate($payment);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'redirect_url' => $result->redirectUrl,
                'reference' => $result->reference,
            ],
        ]);
    }

    public function callback(
        Payment $payment,
        PaymentProcessingService $service
    ): PaymentResource {
        $callback = new PaymentGatewayCallback(
            reference: (string) request()->query('Authority', ''),
            status: (string) request()->query('Status', ''),
            metadata: request()->query(),
        );

        $payment = $service->process($payment, $callback);

        return new PaymentResource($payment);
    }
}
