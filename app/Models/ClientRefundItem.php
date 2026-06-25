<?php

namespace App\Models;

use Database\Factories\ClientRefundItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_refund_id', 'order_allocation_id', 'product_id', 'batch_item_id', 'storage_id', 'qty', 'unit_sale_price', 'unit_cost', 'restock'])]
class ClientRefundItem extends Model
{
    /** @use HasFactory<ClientRefundItemFactory> */
    use HasFactory;

    public function clientRefund(): BelongsTo
    {
        return $this->belongsTo(ClientRefund::class);
    }

    public function orderAllocation(): BelongsTo
    {
        return $this->belongsTo(ClientOrderAllocation::class, 'order_allocation_id');
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
            'unit_sale_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'restock' => 'boolean',
        ];
    }
}
