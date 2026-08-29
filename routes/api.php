<?php

use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionPlanController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Items
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/{item:slug}', [ItemController::class, 'show']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        // Authentication
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Item Access
        Route::get(
            '/items/{item:slug}/access',
            [ItemController::class, 'access']
        );

        Route::get(
            '/items/{item:slug}/content',
            [ItemController::class, 'content']
        );

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        // Payments
        Route::post(
            '/orders/{order}/payments',
            [PaymentController::class, 'store']
        );

        Route::post(
            '/payments/{payment}/process',
            [PaymentController::class, 'process']
        );

        // Subscriptions
        Route::get(
            '/subscriptions',
            [SubscriptionController::class, 'index']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:sanctum', 'admin'])
        ->prefix('admin')
        ->group(function () {

            // Dashboard
            Route::get(
                '/dashboard',
                [DashboardController::class, 'index']
            );

            // Items
            Route::apiResource(
                'items',
                AdminItemController::class
            );

            // Item Media
            Route::post(
                '/items/{item}/media',
                [MediaController::class, 'store']
            );

            Route::delete(
                '/items/{item}/media/{media}',
                [MediaController::class, 'destroy']
            );

            // Orders
            Route::get(
                '/orders',
                [AdminOrderController::class, 'index']
            );

            Route::get(
                '/orders/{order}',
                [AdminOrderController::class, 'show']
            );

            // Subscription Plans
            Route::apiResource(
                'subscription-plans',
                SubscriptionPlanController::class
            )->only([
                'index',
                'store',
                'show',
                'update',
            ]);
        });
});
