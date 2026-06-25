<?php

namespace App\Services;

use App\Enums\IdempotencyOperation;
use App\Exceptions\IdempotencyConflictException;
use App\Models\IdempotencyKey;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IdempotencyService
{
    /** @param Closure(): Model $callback */
    public function execute(
        ?string $key,
        User $user,
        IdempotencyOperation $operation,
        ?Model $scope,
        array $payload,
        Closure $callback,
    ): Model {
        if ($key === null) {
            return $callback();
        }

        $requestHash = hash('sha256', json_encode(
            $this->canonicalize(array_diff_key($payload, ['idempotency_key' => true])),
            JSON_THROW_ON_ERROR,
        ));

        return DB::transaction(function () use ($key, $user, $operation, $scope, $requestHash, $callback): Model {
            IdempotencyKey::query()->insertOrIgnore([
                'key' => $key,
                'user_id' => $user->getKey(),
                'operation' => $operation->value,
                'scope_type' => $scope?->getMorphClass(),
                'scope_id' => $scope?->getKey(),
                'request_hash' => $requestHash,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $reservation = IdempotencyKey::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $reservation->user_id !== $user->getKey()
                || $reservation->operation !== $operation
                || $reservation->scope_type !== $scope?->getMorphClass()
                || $reservation->scope_id !== $scope?->getKey()
                || ! hash_equals($reservation->request_hash, $requestHash)
            ) {
                throw new IdempotencyConflictException;
            }

            if ($reservation->resource_type !== null && $reservation->resource_id !== null) {
                $resourceType = $reservation->resource_type;

                if (! is_a($resourceType, Model::class, true)) {
                    throw new RuntimeException('Stored idempotency resource type is invalid.');
                }

                return $resourceType::query()->findOrFail($reservation->resource_id);
            }

            $resource = $callback();
            $reservation->update([
                'resource_type' => $resource->getMorphClass(),
                'resource_id' => $resource->getKey(),
            ]);

            return $resource;
        });
    }

    private function canonicalize(array $payload): array
    {
        if (! array_is_list($payload)) {
            ksort($payload);
        }

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->canonicalize($value);
            }
        }

        return $payload;
    }
}
