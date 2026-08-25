<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminOrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $orders = Order::latest()->paginate();

        return AdminOrderResource::collection($orders);
    }
}
