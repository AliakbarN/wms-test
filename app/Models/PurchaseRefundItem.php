<?php

namespace App\Models;

use Database\Factories\PurchaseRefundItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_refund_id', 'batch_item_id', 'product_id', 'storage_id', 'qty', 'unit_refund_cost'])]
class PurchaseRefundItem extends Model
{
    /** @use HasFactory<PurchaseRefundItemFactory> */
    use HasFactory;

    public function purchaseRefund(): BelongsTo
    {
        return $this->belongsTo(PurchaseRefund::class);
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
            'unit_refund_cost' => 'decimal:2',
        ];
    }
}
