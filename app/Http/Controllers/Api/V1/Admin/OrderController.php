<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminOrderResource;
use App\Http\Requests\AdminOrderIndexRequest;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(
        AdminOrderIndexRequest $request
    ): AnonymousResourceCollection {
        $query = Order::query();

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')->value
            );
        }

        if ($request->filled('user_id')) {
            $query->where(
                'user_id',
                $request->integer('user_id')
            );
        }

        if ($request->filled('payment_status')) {
            $query->whereHas('payments', function ($query) use ($request) {
                $query->where(
                    'status',
                    $request->string('payment_status')->value
                );
            });
        }

        $orders = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate();

        return AdminOrderResource::collection($orders);
    }

    public function show(Order $order): AdminOrderResource
    {
        $order->load([
            'user',
            'orderItems.item',
            'subscriptionPlan',
            'payments',
        ]);

        return new AdminOrderResource($order);
    }
}
