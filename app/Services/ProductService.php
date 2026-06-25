<?php

namespace App\Services;

use App\Models\BatchItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $categoryChanged = isset($data['category_id'])
                && $data['category_id'] !== $lockedProduct->category_id;

            if ($categoryChanged && BatchItem::query()->where('product_id', $lockedProduct->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'category_id' => ['A product used in a purchase cannot be moved to another category.'],
                ]);
            }

            $lockedProduct->update($data);

            return $lockedProduct->refresh();
        });
    }
}
