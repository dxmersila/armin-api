<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Items
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/{item:slug}', [ItemController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get(
            '/items/{item:slug}/content',
            [ItemController::class, 'content']
        );
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/items/{item:slug}/access', [ItemController::class, 'access']);
    });

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // Orders
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    });

    // Payments
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/orders/{order}/payments', [PaymentController::class, 'store']);
        Route::post('/payments/{payment}/process', [PaymentController::class, 'process']);
    });

    // Dashboard / Admin
    Route::middleware(['auth:sanctum', 'admin'])
        ->prefix('admin')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);

            Route::apiResource('items', AdminItemController::class);

            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::get('/orders/{order}', [AdminOrderController::class, 'show']);

            Route::apiResource(
                'subscription-plans',
                SubscriptionPlanController::class
            )->only([
                'index',
                'store',
                'show',
                'update',
            ]);

            Route::post('/items/{item}/media', [MediaController::class, 'store']);
            Route::delete('/items/{item}/media/{media}', [MediaController::class, 'destroy']);
        });
});
