<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterDataIndexRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(MasterDataIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $results = Product::query()
            ->with('category:id,name')
            ->when(isset($filters['search']), fn ($query) => $query->where(function ($query) use ($filters): void {
                $query->whereLike('name', '%'.$filters['search'].'%')
                    ->orWhereLike('sku', '%'.$filters['search'].'%');
            }))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return $this->paginated($results, ProductResource::class, $request);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::query()->create($request->validated())->load('category:id,name');

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category:id,name'));
    }

    public function update(
        ProductRequest $request,
        Product $product,
        ProductService $productService,
    ): ProductResource {
        return new ProductResource(
            $productService->update($product, $request->validated())->load('category:id,name'),
        );
    }

    public function destroy(Product $product): ProductResource
    {
        $product->update(['is_active' => false]);

        return new ProductResource($product->refresh()->load('category:id,name'));
    }
}
