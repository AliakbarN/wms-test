<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RemainingQuantitiesRequest;
use App\Http\Resources\RemainingQuantityResource;
use App\Services\InventoryReportService;
use Illuminate\Http\JsonResponse;

class RemainingQuantityController extends Controller
{
    public function index(
        RemainingQuantitiesRequest $request,
        InventoryReportService $inventoryReportService,
    ): JsonResponse {
        $filters = $request->validated();
        $report = $inventoryReportService->remainingQuantities($filters);
        $results = $report['results'];

        return response()->json([
            'data' => RemainingQuantityResource::collection($results->getCollection())->resolve($request),
            'meta' => [
                'date' => $filters['date'],
                'timezone' => config('app.timezone'),
                'as_of' => $report['cutoff']->toISOString(),
                'page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }
}
