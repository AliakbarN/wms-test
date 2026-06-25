<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryQueryService
{
    public function availableProducts(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('inventory_balances', 'inventory_balances.product_id', '=', 'products.id')
            ->where('products.is_active', true)
            ->where('inventory_balances.qty_available', '>', 0)
            ->select([
                'products.id',
                'products.name',
                'products.default_sale_price',
                'categories.name as category_name',
                DB::raw('SUM(inventory_balances.qty_available) as qty'),
            ])
            ->when(
                isset($filters['storage_id']),
                fn ($query) => $query->where('inventory_balances.storage_id', $filters['storage_id']),
            )
            ->when(
                isset($filters['provider_id']),
                fn ($query) => $query->where('categories.provider_id', $filters['provider_id']),
            )
            ->when(
                isset($filters['category_id']),
                fn ($query) => $query->where('products.category_id', $filters['category_id']),
            )
            ->when(
                isset($filters['search']),
                fn ($query) => $query->whereLike('products.name', '%'.$filters['search'].'%'),
            )
            ->groupBy([
                'products.id',
                'products.name',
                'products.default_sale_price',
                'categories.name',
            ])
            ->havingRaw('SUM(inventory_balances.qty_available) > 0')
            ->orderBy('products.name')
            ->orderBy('products.id');

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
