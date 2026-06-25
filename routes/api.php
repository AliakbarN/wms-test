<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailableProductController;
use App\Http\Controllers\Api\V1\BatchProfitController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ClientOrderController;
use App\Http\Controllers\Api\V1\ClientRefundController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProviderController;
use App\Http\Controllers\Api\V1\ProviderRefundController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\RemainingQuantityController;
use App\Http\Controllers\Api\V1\StorageController;
use Illuminate\Support\Facades\Route;

Route::post('v1/auth/token', [AuthController::class, 'login'])->middleware('throttle:login');

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::delete('/auth/token', [AuthController::class, 'logout']);
    Route::get('/status', fn () => response()->json(['status' => 'ok']));
    Route::get('/products/available', [AvailableProductController::class, 'index']);
    Route::get('/storages/remaining-quantities', [RemainingQuantityController::class, 'index']);
    Route::get('/batches/profit', [BatchProfitController::class, 'index']);

    Route::apiResource('providers', ProviderController::class)->only(['index', 'show'])->middleware('can:manageMasterData');
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show'])->middleware('can:manageMasterData');
    Route::apiResource('products', ProductController::class)->only(['index', 'show'])->middleware('can:manageMasterData');
    Route::apiResource('storages', StorageController::class)->only(['index', 'show'])->middleware('can:manageMasterData');
    Route::apiResource('clients', ClientController::class)->only(['index', 'show'])->middleware('can:manageMasterData');

    Route::middleware('throttle:api-writes')->group(function (): void {
        Route::post('/purchases', [PurchaseController::class, 'store']);
        Route::post('/client-orders', [ClientOrderController::class, 'store']);
        Route::post('/client-orders/{order}/refunds', [ClientRefundController::class, 'store']);
        Route::post('/batches/{batch}/refunds', [ProviderRefundController::class, 'store']);

        Route::apiResource('providers', ProviderController::class)->only(['store', 'update', 'destroy'])->middleware('can:manageMasterData');
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy'])->middleware('can:manageMasterData');
        Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy'])->middleware('can:manageMasterData');
        Route::apiResource('storages', StorageController::class)->only(['store', 'update', 'destroy'])->middleware('can:manageMasterData');
        Route::apiResource('clients', ClientController::class)->only(['store', 'update', 'destroy'])->middleware('can:manageMasterData');
    });
});
