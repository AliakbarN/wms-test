<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IdempotencyOperation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRefundRequest;
use App\Http\Resources\ClientRefundResource;
use App\Models\ClientOrder;
use App\Services\ClientRefundService;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;

class ClientRefundController extends Controller
{
    public function store(
        StoreClientRefundRequest $request,
        ClientOrder $order,
        ClientRefundService $clientRefundService,
        IdempotencyService $idempotencyService,
    ): JsonResponse {
        $data = $request->validated();
        $refund = $idempotencyService->execute(
            $data['idempotency_key'] ?? null,
            $request->user(),
            IdempotencyOperation::ClientRefund,
            $order,
            $data,
            fn () => $clientRefundService->create($order, $data),
        )->load('items');

        return (new ClientRefundResource($refund))
            ->response()
            ->setStatusCode(201);
    }
}
