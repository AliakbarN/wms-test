<?php

namespace App\Models;

use Database\Factories\ClientOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_id', 'product_id', 'requested_qty', 'unit_sale_price'])]
class ClientOrderItem extends Model
{
    /** @use HasFactory<ClientOrderItemFactory> */
    use HasFactory;

    public function order(): BelongsTo
    {
        return $this->belongsTo(ClientOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ClientOrderAllocation::class, 'order_item_id');
    }

    protected function casts(): array
    {
        return [
            'requested_qty' => 'integer',
            'unit_sale_price' => 'decimal:2',
        ];
    }
}
