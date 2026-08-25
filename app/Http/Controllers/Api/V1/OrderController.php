<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('orderItems.item')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate();

        return OrderResource::collection($orders);
    }

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
