<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderCancellationService;
use App\Services\OrderCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\OrderType;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionOrderCreationService;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'orderItems.item.categories',
                'orderItems.item.tags',
                'orderItems.item.media.file',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate();

        return OrderResource::collection($orders);
    }

    public function show(
        Order $order,
        Request $request
    ): OrderResource {
        abort_unless(
            $order->user_id === $request->user()->id,
            404
        );

        $order->load([
            'orderItems.item.categories',
            'orderItems.item.tags',
            'orderItems.item.media.file',
            'subscriptionPlan',
            'payments',
        ]);

        return new OrderResource($order);
    }

    public function destroy(
        Order $order,
        Request $request,
        OrderCancellationService $orderCancellationService,
    ): Response {
        abort_unless(
            $order->user_id === $request->user()->id,
            404
        );

        $orderCancellationService->cancel($order);

        return response()->noContent();
    }

    public function store(
        CreateOrderRequest $request,
        OrderCreationService $orderCreationService,
        SubscriptionOrderCreationService $subscriptionOrderCreationService,
    ): JsonResponse {
        $order = match ($request->validated('type')) {
            OrderType::ITEM->value => $orderCreationService->create(
                $request->user(),
                $request->validated('item_ids')
            ),

            OrderType::SUBSCRIPTION->value => $subscriptionOrderCreationService->create(
                $request->user(),
                SubscriptionPlan::query()->findOrFail(
                    $request->validated('subscription_plan_id')
                )
            ),
        };

        $order->load([
            'orderItems.item',
            'subscriptionPlan',
        ]);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(
                $order->wasRecentlyCreated
                    ? Response::HTTP_CREATED
                    : Response::HTTP_OK
            );
    }
}
