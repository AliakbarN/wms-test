<?php

namespace App\Models;

use Database\Factories\ClientOrderAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_item_id', 'batch_item_id', 'product_id', 'storage_id', 'qty', 'unit_cost', 'unit_sale_price'])]
class ClientOrderAllocation extends Model
{
    /** @use HasFactory<ClientOrderAllocationFactory> */
    use HasFactory;

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(ClientOrderItem::class, 'order_item_id');
    }

    public function batchItem(): BelongsTo
    {
        return $this->belongsTo(BatchItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function storage(): BelongsTo
    {
        return $this->belongsTo(Storage::class);
    }

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'unit_cost' => 'decimal:2',
            'unit_sale_price' => 'decimal:2',
        ];
    }
}
