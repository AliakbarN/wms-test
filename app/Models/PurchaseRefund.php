<?php

namespace App\Models;

use Database\Factories\PurchaseRefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['batch_id', 'provider_id', 'refunded_at', 'reason', 'idempotency_key'])]
class PurchaseRefund extends Model
{
    /** @use HasFactory<PurchaseRefundFactory> */
    use HasFactory;

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRefundItem::class);
    }

    protected function casts(): array
    {
        return ['refunded_at' => 'datetime'];
    }
}
