<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailableProductsRequest;
use App\Http\Resources\AvailableProductResource;
use App\Services\InventoryQueryService;
use Illuminate\Http\JsonResponse;

class AvailableProductController extends Controller
{
    public function index(
        AvailableProductsRequest $request,
        InventoryQueryService $inventoryQueryService,
    ): JsonResponse {
        $products = $inventoryQueryService->availableProducts($request->validated());

        return response()->json([
            'data' => AvailableProductResource::collection($products->getCollection())->resolve($request),
            'meta' => [
                'page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}
