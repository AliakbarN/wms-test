<?php

namespace App\Models;

use App\Enums\IdempotencyOperation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'user_id',
    'operation',
    'scope_type',
    'scope_id',
    'request_hash',
    'resource_type',
    'resource_id',
])]
class IdempotencyKey extends Model
{
    protected function casts(): array
    {
        return [
            'operation' => IdempotencyOperation::class,
        ];
    }
}
