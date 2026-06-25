<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemainingQuantityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'storage_id' => $this->storage_id,
            'storage_name' => $this->storage_name,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'category_name' => $this->category_name,
            'qty' => (int) $this->qty,
        ];
    }
}
