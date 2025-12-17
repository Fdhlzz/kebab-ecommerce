<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingRateController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CourierDeliveryController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\UserAddressController;
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::get('/districts', [RegionController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/user/address', [AuthController::class, 'updateAddress']);
    Route::get('/addresses', [UserAddressController::class, 'index']);
    Route::post('/addresses', [UserAddressController::class, 'store']);
    Route::put('/addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('/addresses/{id}', [UserAddressController::class, 'destroy']);
    Route::post('/addresses/{id}/primary', [UserAddressController::class, 'setPrimary']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::match(['put', 'post'], '/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/payment-proof', [OrderController::class, 'uploadPaymentProof']);
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::get('shipping-rates', [ShippingRateController::class, 'index']);
    Route::post('shipping-rates', [ShippingRateController::class, 'store']);
    Route::delete('shipping-rates/{region_code}', [ShippingRateController::class, 'destroy']);

    Route::apiResource('couriers', CourierController::class)->parameters([
        'couriers' => 'user'
    ]);

});

Route::middleware(['auth:sanctum', 'role:kurir'])->group(function () {
    Route::get('/courier/assignments', [CourierDeliveryController::class, 'index']);
    Route::post('/courier/orders/{order}/complete', [CourierDeliveryController::class, 'completeOrder']);
});
