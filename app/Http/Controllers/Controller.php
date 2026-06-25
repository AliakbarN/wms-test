<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function paginated(LengthAwarePaginator $items, string $resource, Request $request): JsonResponse
    {
        return response()->json([
            'data' => $resource::collection($items->getCollection())->resolve($request),
            'meta' => [
                'page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
