<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileInventory extends Command
{
    protected $signature = 'inventory:reconcile';

    protected $description = 'Report mismatches between inventory balances and the stock movement ledger';

    public function handle(): int
    {
        $movementTotals = DB::table('stock_movements')
            ->selectRaw('batch_item_id')
            ->selectRaw('MAX(product_id) AS product_id')
            ->selectRaw('MAX(storage_id) AS storage_id')
            ->selectRaw('SUM(qty_delta) AS movement_sum')
            ->groupBy('batch_item_id');

        $batchItemIds = DB::table('batch_items')
            ->selectRaw('id AS batch_item_id')
            ->union(DB::table('inventory_balances')->select('batch_item_id'))
            ->union(DB::table('stock_movements')->select('batch_item_id'));

        $mismatches = DB::query()
            ->fromSub($batchItemIds, 'ids')
            ->leftJoin('batch_items', 'batch_items.id', '=', 'ids.batch_item_id')
            ->leftJoin('inventory_balances', 'inventory_balances.batch_item_id', '=', 'ids.batch_item_id')
            ->leftJoinSub($movementTotals, 'movements', fn ($join) => $join->on('movements.batch_item_id', '=', 'ids.batch_item_id'))
            ->where(function ($query): void {
                $query
                    ->where(function ($query): void {
                        $query->whereNull('inventory_balances.id')
                            ->whereNotNull('movements.batch_item_id');
                    })
                    ->orWhere(function ($query): void {
                        $query->whereNull('batch_items.id')
                            ->whereNotNull('inventory_balances.id');
                    })
                    ->orWhereRaw('COALESCE(inventory_balances.qty_available, 0) <> COALESCE(movements.movement_sum, 0)')
                    ->orWhere('inventory_balances.qty_available', '<', 0);
            })
            ->orderBy('ids.batch_item_id')
            ->selectRaw('ids.batch_item_id')
            ->selectRaw('COALESCE(inventory_balances.product_id, movements.product_id, batch_items.product_id) AS product_id')
            ->selectRaw('COALESCE(inventory_balances.storage_id, movements.storage_id, batch_items.storage_id) AS storage_id')
            ->selectRaw('inventory_balances.qty_available AS balance_qty')
            ->selectRaw('COALESCE(movements.movement_sum, 0) AS movement_sum')
            ->selectRaw('COALESCE(inventory_balances.qty_available, 0) - COALESCE(movements.movement_sum, 0) AS difference')
            ->get();

        if ($mismatches->isEmpty()) {
            $this->info('Inventory balances match the stock movement ledger.');

            return self::SUCCESS;
        }

        $this->error("Found {$mismatches->count()} inventory reconciliation mismatch(es).");
        $this->table(
            ['batch_item_id', 'product_id', 'storage_id', 'balance_qty', 'movement_sum', 'difference'],
            $mismatches->map(fn ($row): array => [
                $row->batch_item_id,
                $row->product_id ?? '-',
                $row->storage_id ?? '-',
                $row->balance_qty ?? 'MISSING',
                $row->movement_sum,
                $row->difference,
            ])->all(),
        );

        return self::FAILURE;
    }
}
