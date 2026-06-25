<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'ordered_at' => $this->ordered_at->toISOString(),
            'status' => $this->status->value,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'qty' => $item->requested_qty,
                'unit_sale_price' => $item->unit_sale_price,
                'allocations' => $item->relationLoaded('allocations')
                    ? $item->allocations->map(fn ($allocation): array => [
                        'id' => $allocation->id,
                        'batch_item_id' => $allocation->batch_item_id,
                        'batch_id' => $allocation->batchItem->batch_id,
                        'storage_id' => $allocation->storage_id,
                        'qty' => $allocation->qty,
                        'unit_cost' => $allocation->unit_cost,
                        'unit_sale_price' => $allocation->unit_sale_price,
                    ])
                    : [],
            ])),
        ];
    }
}
