<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\MasterDataIndexRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(MasterDataIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $results = Category::query()
            ->when(isset($filters['search']), fn ($query) => $query->whereLike('name', '%'.$filters['search'].'%'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return $this->paginated($results, CategoryResource::class, $request);
    }

    public function store(CategoryRequest $request, CategoryService $categoryService): JsonResponse
    {
        $category = $categoryService->create($request->validated());

        return (new CategoryResource($category))
            ->response()->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function update(
        CategoryRequest $request,
        Category $category,
        CategoryService $categoryService,
    ): CategoryResource {
        return new CategoryResource($categoryService->update($category, $request->validated()));
    }

    public function destroy(Category $category): CategoryResource
    {
        $category->update(['is_active' => false]);

        return new CategoryResource($category->refresh());
    }
}
