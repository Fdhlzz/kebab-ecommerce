<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingRateController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CourierController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::get('shipping-rates', [ShippingRateController::class, 'index']);
    Route::post('shipping-rates', [ShippingRateController::class, 'store']);
    Route::delete('shipping-rates/{region_code}', [ShippingRateController::class, 'destroy']);
    Route::apiResource('couriers', CourierController::class)->parameters([
        'couriers' => 'user'
    ]);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus']);
});
