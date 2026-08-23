<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
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
        Route::post('/orders', [OrderController::class, 'store']);
    });

    // Dashboard / Admin
    Route::middleware(['auth:sanctum', 'admin'])
        ->prefix('admin')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index']);

            Route::apiResource('items', AdminItemController::class);

            Route::post('/items/{item}/media', [MediaController::class, 'store']);
            Route::delete('/items/{item}/media/{media}', [MediaController::class, 'destroy']);
        });
});
