<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IdempotencyOperation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientOrderRequest;
use App\Http\Resources\ClientOrderResource;
use App\Services\ClientOrderService;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;

class ClientOrderController extends Controller
{
    public function store(
        StoreClientOrderRequest $request,
        ClientOrderService $clientOrderService,
        IdempotencyService $idempotencyService,
    ): JsonResponse {
        $data = $request->validated();
        $order = $idempotencyService->execute(
            $data['idempotency_key'] ?? null,
            $request->user(),
            IdempotencyOperation::ClientOrder,
            null,
            $data,
            fn () => $clientOrderService->create($data),
        )->load('items.allocations.batchItem:id,batch_id');

        return (new ClientOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
