<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'sku' => $this->sku,
            'name' => $this->name,
            'default_sale_price' => $this->default_sale_price,
            'is_active' => $this->is_active,
        ];
    }
}
