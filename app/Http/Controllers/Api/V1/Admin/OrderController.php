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
            $query->where('status', $request->string('status')->value);
        }

        $orders = $query
            ->latest()
            ->paginate();

        return AdminOrderResource::collection($orders);
    }

    public function show(Order $order): AdminOrderResource
    {
        $order->load([
            'user',
            'orderItems.item',
            'payments',
        ]);

        return new AdminOrderResource($order);
    }
}
