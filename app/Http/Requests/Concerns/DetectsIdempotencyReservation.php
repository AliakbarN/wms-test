<?php

namespace App\Http\Requests\Concerns;

use App\Models\IdempotencyKey;

trait DetectsIdempotencyReservation
{
    protected function hasReservedIdempotencyKey(): bool
    {
        $key = $this->input('idempotency_key');

        return is_string($key)
            && $key !== ''
            && IdempotencyKey::query()->where('key', $key)->exists();
    }
}
