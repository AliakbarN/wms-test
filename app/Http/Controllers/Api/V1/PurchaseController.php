<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IdempotencyOperation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Services\IdempotencyService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function store(
        StorePurchaseRequest $request,
        PurchaseService $purchaseService,
        IdempotencyService $idempotencyService,
    ): JsonResponse {
        $data = $request->validated();
        $batch = $idempotencyService->execute(
            $data['idempotency_key'] ?? null,
            $request->user(),
            IdempotencyOperation::Purchase,
            null,
            $data,
            fn () => $purchaseService->create($data),
        )->load('items');

        return (new PurchaseResource($batch))
            ->response()
            ->setStatusCode(201);
    }
}
