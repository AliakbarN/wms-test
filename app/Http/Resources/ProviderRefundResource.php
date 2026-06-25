<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderRefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'provider_id' => $this->provider_id,
            'refunded_at' => $this->refunded_at->toISOString(),
            'reason' => $this->reason,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'batch_item_id' => $item->batch_item_id,
                'product_id' => $item->product_id,
                'storage_id' => $item->storage_id,
                'qty' => $item->qty,
                'unit_refund_cost' => $item->unit_refund_cost,
            ])),
        ];
    }
}
