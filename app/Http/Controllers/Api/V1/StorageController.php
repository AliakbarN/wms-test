<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterDataIndexRequest;
use App\Http\Requests\StorageRequest;
use App\Http\Resources\StorageResource;
use App\Models\Storage;
use Illuminate\Http\JsonResponse;

class StorageController extends Controller
{
    public function index(MasterDataIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $results = Storage::query()
            ->when(isset($filters['search']), fn ($query) => $query->whereLike('name', '%'.$filters['search'].'%'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return $this->paginated($results, StorageResource::class, $request);
    }

    public function store(StorageRequest $request): JsonResponse
    {
        $storage = Storage::query()->create($request->validated());

        return (new StorageResource($storage))
            ->response()->setStatusCode(201);
    }

    public function show(Storage $storage): StorageResource
    {
        return new StorageResource($storage);
    }

    public function update(StorageRequest $request, Storage $storage): StorageResource
    {
        $storage->update($request->validated());

        return new StorageResource($storage->refresh());
    }

    public function destroy(Storage $storage): StorageResource
    {
        $storage->update(['is_active' => false]);

        return new StorageResource($storage->refresh());
    }
}
