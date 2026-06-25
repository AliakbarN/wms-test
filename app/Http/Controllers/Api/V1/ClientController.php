<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Requests\MasterDataIndexRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(MasterDataIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $results = Client::query()
            ->when(isset($filters['search']), fn ($query) => $query->where(function ($query) use ($filters): void {
                $query->whereLike('name', '%'.$filters['search'].'%')
                    ->orWhereLike('email', '%'.$filters['search'].'%');
            }))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 20);

        return $this->paginated($results, ClientResource::class, $request);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = Client::query()->create($request->validated());

        return (new ClientResource($client))
            ->response()->setStatusCode(201);
    }

    public function show(Client $client): ClientResource
    {
        return new ClientResource($client);
    }

    public function update(ClientRequest $request, Client $client): ClientResource
    {
        $client->update($request->validated());

        return new ClientResource($client->refresh());
    }

    public function destroy(Client $client): ClientResource
    {
        $client->update(['is_active' => false]);

        return new ClientResource($client->refresh());
    }
}
