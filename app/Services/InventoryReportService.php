<?php

namespace App\Services;

use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    /**
     * @return array{results: LengthAwarePaginator, cutoff: CarbonImmutable}
     */
    public function remainingQuantities(array $filters): array
    {
        $cutoff = CarbonImmutable::parse($filters['date'], config('app.timezone'))
            ->endOfDay()
            ->utc();

        $query = StockMovement::query()
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('storages', 'storages.id', '=', 'stock_movements.storage_id')
            ->where('stock_movements.occurred_at', '<=', $cutoff)
            ->select([
                'storages.id as storage_id',
                'storages.name as storage_name',
                'products.id as product_id',
                'products.name as product_name',
                'categories.name as category_name',
                DB::raw('SUM(stock_movements.qty_delta) as qty'),
            ])
            ->when(
                isset($filters['storage_id']),
                fn ($query) => $query->where('stock_movements.storage_id', $filters['storage_id']),
            )
            ->when(
                isset($filters['product_id']),
                fn ($query) => $query->where('stock_movements.product_id', $filters['product_id']),
            )
            ->when(
                isset($filters['category_id']),
                fn ($query) => $query->where('products.category_id', $filters['category_id']),
            )
            ->when(
                isset($filters['provider_id']),
                fn ($query) => $query->where('categories.provider_id', $filters['provider_id']),
            )
            ->groupBy([
                'storages.id',
                'storages.name',
                'products.id',
                'products.name',
                'categories.name',
            ])
            ->havingRaw('SUM(stock_movements.qty_delta) > 0')
            ->orderBy('storages.name')
            ->orderBy('storages.id')
            ->orderBy('products.name')
            ->orderBy('products.id');

        return [
            'results' => $query->paginate($filters['per_page'] ?? 20),
            'cutoff' => $cutoff,
        ];
    }
}
