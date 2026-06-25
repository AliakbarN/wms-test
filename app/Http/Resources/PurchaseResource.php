<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_no' => $this->batch_no,
            'provider_id' => $this->provider_id,
            'purchased_at' => $this->purchased_at->toISOString(),
            'status' => $this->status->value,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'storage_id' => $item->storage_id,
                'qty' => $item->purchased_qty,
                'unit_cost' => $item->unit_cost,
            ])),
        ];
    }
}
