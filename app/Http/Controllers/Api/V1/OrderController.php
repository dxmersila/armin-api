<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function store(
        CreateOrderRequest $request,
        OrderCreationService $orderCreationService
    ): JsonResponse {
        $order = $orderCreationService->create(
            $request->user(),
            $request->validated('item_ids')
        );

        $order->load([
            'orderItems.item',
        ]);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
