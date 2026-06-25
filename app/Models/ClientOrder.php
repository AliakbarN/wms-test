<?php

namespace App\Models;

use App\Enums\ClientOrderStatus;
use Database\Factories\ClientOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_no', 'client_id', 'ordered_at', 'status', 'idempotency_key'])]
class ClientOrder extends Model
{
    /** @use HasFactory<ClientOrderFactory> */
    use HasFactory;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClientOrderItem::class, 'order_id');
    }

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'status' => ClientOrderStatus::class,
        ];
    }
}
