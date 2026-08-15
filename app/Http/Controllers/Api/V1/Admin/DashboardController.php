<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'users_count' => User::count(),
            'items_count' => Item::count(),
            'categories_count' => Category::count(),
        ]);
    }
}
