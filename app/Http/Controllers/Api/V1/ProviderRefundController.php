<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IdempotencyOperation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderRefundRequest;
use App\Http\Resources\ProviderRefundResource;
use App\Models\Batch;
use App\Services\IdempotencyService;
use App\Services\ProviderRefundService;
use Illuminate\Http\JsonResponse;

class ProviderRefundController extends Controller
{
    public function store(
        StoreProviderRefundRequest $request,
        Batch $batch,
        ProviderRefundService $providerRefundService,
        IdempotencyService $idempotencyService,
    ): JsonResponse {
        $data = $request->validated();
        $refund = $idempotencyService->execute(
            $data['idempotency_key'] ?? null,
            $request->user(),
            IdempotencyOperation::ProviderRefund,
            $batch,
            $data,
            fn () => $providerRefundService->create($batch, $data),
        )->load('items');

        return (new ProviderRefundResource($refund))
            ->response()
            ->setStatusCode(201);
    }
}
