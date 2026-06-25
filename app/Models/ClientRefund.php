<?php

namespace App\Models;

use Database\Factories\ClientRefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_id', 'client_id', 'refunded_at', 'reason', 'idempotency_key'])]
class ClientRefund extends Model
{
    /** @use HasFactory<ClientRefundFactory> */
    use HasFactory;

    public function order(): BelongsTo
    {
        return $this->belongsTo(ClientOrder::class, 'order_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClientRefundItem::class);
    }

    protected function casts(): array
    {
        return ['refunded_at' => 'datetime'];
    }
}
