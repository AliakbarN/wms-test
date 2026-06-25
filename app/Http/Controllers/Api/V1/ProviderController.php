<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterDataIndexRequest;
use App\Http\Requests\ProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;

class ProviderController extends Controller
{
    public function index(MasterDataIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $results = Provider::query()
            ->when(isset($filters['search']), fn ($query) => $query->whereLike('name', '%'.$filters['search'].'%'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return $this->paginated($results, ProviderResource::class, $request);
    }

    public function store(ProviderRequest $request): JsonResponse
    {
        $provider = Provider::query()->create($request->validated());

        return (new ProviderResource($provider))
            ->response()->setStatusCode(201);
    }

    public function show(Provider $provider): ProviderResource
    {
        return new ProviderResource($provider);
    }

    public function update(ProviderRequest $request, Provider $provider): ProviderResource
    {
        $provider->update($request->validated());

        return new ProviderResource($provider->refresh());
    }

    public function destroy(Provider $provider): ProviderResource
    {
        $provider->update(['is_active' => false]);

        return new ProviderResource($provider->refresh());
    }
}
