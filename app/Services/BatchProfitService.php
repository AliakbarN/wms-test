<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;

class BatchProfitService
{
    public function __construct(private readonly BatchProfitQuery $query) {}

    public function report(array $filters): LengthAwarePaginator
    {
        $page = $this->batches($filters);
        $batchIds = collect($page->items())->pluck('id')->all();

        if ($batchIds === []) {
            return $this->paginator([], $page);
        }

        $baseQuery = $this->query->forBatches($batchIds, $filters['product_id'] ?? null);
        $rows = DB::query()
            ->fromSub($baseQuery, 'report')
            ->select('report.*')
            ->selectRaw('(gross_sold_qty - client_refunded_qty) AS net_sold_qty')
            ->selectRaw('(gross_sales_revenue - client_refund_value) AS net_sales_revenue')
            ->selectRaw('(gross_cogs - refunded_cogs) AS cogs')
            ->selectRaw('(gross_sales_revenue - client_refund_value - gross_cogs + refunded_cogs) AS realized_profit')
            ->selectRaw('(gross_sales_revenue - client_refund_value + provider_refund_value + remaining_inventory_value - purchase_cost_total) AS batch_financial_position')
            ->orderByDesc('purchased_at')
            ->orderByDesc('batch_id')
            ->get();

        return $this->paginator($rows, $page);
    }

    private function batches(array $filters): LengthAwarePaginator
    {
        return DB::table('batches')
            ->join('batch_items', 'batch_items.batch_id', '=', 'batches.id')
            ->when(isset($filters['batch_id']), fn ($query) => $query->where('batches.id', $filters['batch_id']))
            ->when(isset($filters['provider_id']), fn ($query) => $query->where('batches.provider_id', $filters['provider_id']))
            ->when(isset($filters['product_id']), fn ($query) => $query->where('batch_items.product_id', $filters['product_id']))
            ->when(isset($filters['from']), fn ($query) => $query->where('batches.purchased_at', '>=', $this->startOfDay($filters['from'])))
            ->when(isset($filters['to']), fn ($query) => $query->where('batches.purchased_at', '<=', $this->endOfDay($filters['to'])))
            ->select(['batches.id', 'batches.purchased_at'])
            ->distinct()
            ->orderByDesc('batches.purchased_at')
            ->orderByDesc('batches.id')
            ->paginate($filters['per_page'] ?? 20);
    }

    private function paginator($items, LengthAwarePaginator $page): LengthAwarePaginator
    {
        return new Paginator(
            $items,
            $page->total(),
            $page->perPage(),
            $page->currentPage(),
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    private function startOfDay(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, config('app.timezone'))->startOfDay()->utc();
    }

    private function endOfDay(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, config('app.timezone'))->endOfDay()->utc();
    }
}
