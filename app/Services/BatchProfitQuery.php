<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BatchProfitQuery
{
    public function forBatches(array $batchIds, ?int $productId): Builder
    {
        $providerRefunds = $this->providerRefunds($batchIds, $productId);
        $sales = $this->sales($batchIds, $productId);
        $clientRefunds = $this->clientRefunds($batchIds, $productId);

        return DB::table('batches')
            ->join('providers', 'providers.id', '=', 'batches.provider_id')
            ->join('batch_items', 'batch_items.batch_id', '=', 'batches.id')
            ->leftJoin('inventory_balances', 'inventory_balances.batch_item_id', '=', 'batch_items.id')
            ->leftJoinSub($providerRefunds, 'provider_refunds', fn ($join) => $join->on('provider_refunds.batch_id', '=', 'batches.id'))
            ->leftJoinSub($sales, 'sales', fn ($join) => $join->on('sales.batch_id', '=', 'batches.id'))
            ->leftJoinSub($clientRefunds, 'client_refunds', fn ($join) => $join->on('client_refunds.batch_id', '=', 'batches.id'))
            ->whereIn('batches.id', $batchIds)
            ->when($productId !== null, fn ($query) => $query->where('batch_items.product_id', $productId))
            ->groupBy([
                'batches.id',
                'batches.batch_no',
                'batches.provider_id',
                'batches.purchased_at',
                'batches.status',
                'providers.name',
                'provider_refunds.provider_refunded_qty',
                'provider_refunds.provider_refund_value',
                'sales.gross_sold_qty',
                'sales.gross_sales_revenue',
                'sales.gross_cogs',
                'client_refunds.client_refunded_qty',
                'client_refunds.client_refund_value',
                'client_refunds.refunded_cogs',
            ])
            ->select([
                'batches.id as batch_id',
                'batches.batch_no',
                'batches.provider_id',
                'providers.name as provider_name',
                'batches.purchased_at',
                'batches.status',
            ])
            ->selectRaw('SUM(batch_items.purchased_qty) AS purchased_qty')
            ->selectRaw('SUM(batch_items.purchased_qty * batch_items.unit_cost) AS purchase_cost_total')
            ->selectRaw('COALESCE(provider_refunds.provider_refunded_qty, 0) AS provider_refunded_qty')
            ->selectRaw('COALESCE(provider_refunds.provider_refund_value, 0) AS provider_refund_value')
            ->selectRaw('COALESCE(sales.gross_sold_qty, 0) AS gross_sold_qty')
            ->selectRaw('COALESCE(sales.gross_sales_revenue, 0) AS gross_sales_revenue')
            ->selectRaw('COALESCE(sales.gross_cogs, 0) AS gross_cogs')
            ->selectRaw('COALESCE(client_refunds.client_refunded_qty, 0) AS client_refunded_qty')
            ->selectRaw('COALESCE(client_refunds.client_refund_value, 0) AS client_refund_value')
            ->selectRaw('COALESCE(client_refunds.refunded_cogs, 0) AS refunded_cogs')
            ->selectRaw('COALESCE(SUM(inventory_balances.qty_available), 0) AS remaining_qty')
            ->selectRaw('COALESCE(SUM(inventory_balances.qty_available * batch_items.unit_cost), 0) AS remaining_inventory_value');
    }

    private function providerRefunds(array $batchIds, ?int $productId): Builder
    {
        return DB::table('purchase_refund_items')
            ->join('batch_items', 'batch_items.id', '=', 'purchase_refund_items.batch_item_id')
            ->whereIn('batch_items.batch_id', $batchIds)
            ->when($productId !== null, fn ($query) => $query->where('purchase_refund_items.product_id', $productId))
            ->groupBy('batch_items.batch_id')
            ->selectRaw('batch_items.batch_id')
            ->selectRaw('SUM(purchase_refund_items.qty) AS provider_refunded_qty')
            ->selectRaw('SUM(purchase_refund_items.qty * purchase_refund_items.unit_refund_cost) AS provider_refund_value');
    }

    private function sales(array $batchIds, ?int $productId): Builder
    {
        return DB::table('client_order_allocations')
            ->join('batch_items', 'batch_items.id', '=', 'client_order_allocations.batch_item_id')
            ->whereIn('batch_items.batch_id', $batchIds)
            ->when($productId !== null, fn ($query) => $query->where('client_order_allocations.product_id', $productId))
            ->groupBy('batch_items.batch_id')
            ->selectRaw('batch_items.batch_id')
            ->selectRaw('SUM(client_order_allocations.qty) AS gross_sold_qty')
            ->selectRaw('SUM(client_order_allocations.qty * client_order_allocations.unit_sale_price) AS gross_sales_revenue')
            ->selectRaw('SUM(client_order_allocations.qty * client_order_allocations.unit_cost) AS gross_cogs');
    }

    private function clientRefunds(array $batchIds, ?int $productId): Builder
    {
        return DB::table('client_refund_items')
            ->join('batch_items', 'batch_items.id', '=', 'client_refund_items.batch_item_id')
            ->whereIn('batch_items.batch_id', $batchIds)
            ->when($productId !== null, fn ($query) => $query->where('client_refund_items.product_id', $productId))
            ->groupBy('batch_items.batch_id')
            ->selectRaw('batch_items.batch_id')
            ->selectRaw('SUM(client_refund_items.qty) AS client_refunded_qty')
            ->selectRaw('SUM(client_refund_items.qty * client_refund_items.unit_sale_price) AS client_refund_value')
            ->selectRaw('SUM(client_refund_items.qty * client_refund_items.unit_cost) AS refunded_cogs');
    }
}
