<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientRefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'client_id' => $this->client_id,
            'refunded_at' => $this->refunded_at->toISOString(),
            'reason' => $this->reason,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'order_allocation_id' => $item->order_allocation_id,
                'product_id' => $item->product_id,
                'batch_item_id' => $item->batch_item_id,
                'storage_id' => $item->storage_id,
                'qty' => $item->qty,
                'unit_sale_price' => $item->unit_sale_price,
                'unit_cost' => $item->unit_cost,
                'restock' => $item->restock,
            ])),
        ];
    }
}
