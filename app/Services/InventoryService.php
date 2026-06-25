<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\BatchItem;
use App\Models\InventoryBalance;
use App\Models\StockMovement;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function createInitialBalance(
        BatchItem $batchItem,
        int $qty,
        CarbonInterface $occurredAt,
        string $sourceType,
        int $sourceId,
    ): InventoryBalance {
        $this->assertPositiveQuantity($qty);

        return $this->withinTransaction(function () use ($batchItem, $qty, $occurredAt, $sourceType, $sourceId): InventoryBalance {
            $balance = InventoryBalance::query()->create([
                'batch_item_id' => $batchItem->getKey(),
                'product_id' => $batchItem->product_id,
                'storage_id' => $batchItem->storage_id,
                'qty_available' => $qty,
            ]);

            $this->createMovement(
                $batchItem,
                $qty,
                StockMovementType::PurchaseIn,
                $occurredAt,
                $sourceType,
                $sourceId,
            );

            return $balance;
        });
    }

    public function decreaseAvailable(
        BatchItem $batchItem,
        int $qty,
        StockMovementType $movementType,
        CarbonInterface $occurredAt,
        string $sourceType,
        int $sourceId,
    ): InventoryBalance {
        $this->assertPositiveQuantity($qty);
        if (! in_array($movementType, [StockMovementType::ProviderRefundOut, StockMovementType::SaleOut], true)) {
            throw new InvalidArgumentException('Inventory decreases require a provider_refund_out or sale_out movement.');
        }

        return $this->withinTransaction(function () use ($batchItem, $qty, $movementType, $occurredAt, $sourceType, $sourceId): InventoryBalance {
            $balance = $this->lockBalance($batchItem);

            if ($balance->qty_available < $qty) {
                throw new InsufficientStockException($qty, $balance->qty_available);
            }

            $balance->update([
                'qty_available' => $balance->qty_available - $qty,
            ]);

            $this->createMovement(
                $batchItem,
                -$qty,
                $movementType,
                $occurredAt,
                $sourceType,
                $sourceId,
            );

            return $balance;
        });
    }

    public function increaseAvailable(
        BatchItem $batchItem,
        int $qty,
        StockMovementType $movementType,
        CarbonInterface $occurredAt,
        string $sourceType,
        int $sourceId,
    ): InventoryBalance {
        $this->assertPositiveQuantity($qty);
        if ($movementType !== StockMovementType::ClientRefundIn) {
            throw new InvalidArgumentException('Inventory increases require a client_refund_in movement.');
        }

        return $this->withinTransaction(function () use ($batchItem, $qty, $movementType, $occurredAt, $sourceType, $sourceId): InventoryBalance {
            $balance = $this->lockBalance($batchItem);
            $balance->update([
                'qty_available' => $balance->qty_available + $qty,
            ]);

            $this->createMovement(
                $batchItem,
                $qty,
                $movementType,
                $occurredAt,
                $sourceType,
                $sourceId,
            );

            return $balance;
        });
    }

    public function createMovement(
        BatchItem $batchItem,
        int $qtyDelta,
        StockMovementType $movementType,
        CarbonInterface $occurredAt,
        string $sourceType,
        int $sourceId,
    ): StockMovement {
        if ($qtyDelta === 0) {
            throw new InvalidArgumentException('Stock movement quantity cannot be zero.');
        }

        $this->assertMovementDirection($movementType, $qtyDelta);

        return StockMovement::query()->create([
            'product_id' => $batchItem->product_id,
            'batch_item_id' => $batchItem->getKey(),
            'storage_id' => $batchItem->storage_id,
            'movement_type' => $movementType,
            'qty_delta' => $qtyDelta,
            'occurred_at' => $occurredAt,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    public function getAvailableQtyForBatchItem(BatchItem $batchItem): int
    {
        return (int) InventoryBalance::query()
            ->where('batch_item_id', $batchItem->getKey())
            ->value('qty_available');
    }

    private function lockBalance(BatchItem $batchItem): InventoryBalance
    {
        return InventoryBalance::query()
            ->where('batch_item_id', $batchItem->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertMovementDirection(StockMovementType $type, int $qtyDelta): void
    {
        $isPositiveType = in_array($type, [
            StockMovementType::PurchaseIn,
            StockMovementType::ClientRefundIn,
        ], true);
        $isNegativeType = in_array($type, [
            StockMovementType::ProviderRefundOut,
            StockMovementType::SaleOut,
        ], true);

        if (($isPositiveType && $qtyDelta < 0) || ($isNegativeType && $qtyDelta > 0)) {
            throw new InvalidArgumentException("Stock movement direction does not match {$type->value}.");
        }

        if (! $isPositiveType && ! $isNegativeType) {
            throw new InvalidArgumentException("Stock movement type {$type->value} is not implemented.");
        }
    }

    private function assertPositiveQuantity(int $qty): void
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Inventory quantity must be greater than zero.');
        }
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function withinTransaction(Closure $callback): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
