<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BatchProfitRequest;
use App\Http\Resources\BatchProfitResource;
use App\Services\BatchProfitService;
use Illuminate\Http\JsonResponse;

class BatchProfitController extends Controller
{
    public function index(BatchProfitRequest $request, BatchProfitService $batchProfitService): JsonResponse
    {
        $results = $batchProfitService->report($request->validated());

        return response()->json([
            'data' => BatchProfitResource::collection($results->getCollection())->resolve($request),
            'meta' => [
                'page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }
}
