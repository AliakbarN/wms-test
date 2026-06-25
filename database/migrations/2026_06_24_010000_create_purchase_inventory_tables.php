<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->string('batch_no')->nullable()->unique();
            $table->timestamp('purchased_at');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['provider_id', 'purchased_at']);
        });

        Schema::create('batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->restrictOnDelete();
            $table->integer('purchased_qty');
            $table->decimal('unit_cost', 14, 2);
            $table->timestamps();

            $table->index('batch_id');
            $table->index('product_id');
            $table->index('storage_id');
            $table->index(['product_id', 'storage_id']);
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_item_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->restrictOnDelete();
            $table->integer('qty_available');
            $table->timestamps();

            $table->index(['product_id', 'qty_available']);
            $table->index(['storage_id', 'qty_available']);
            $table->index(['product_id', 'storage_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->restrictOnDelete();
            $table->string('movement_type');
            $table->integer('qty_delta');
            $table->timestamp('occurred_at');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->timestamps();

            $table->index(['product_id', 'storage_id', 'occurred_at']);
            $table->index(['batch_item_id', 'occurred_at']);
            $table->index(['source_type', 'source_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                ALTER TABLE batches
                    ADD CONSTRAINT batches_status_check
                    CHECK (status IN ('confirmed', 'partially_refunded', 'fully_refunded', 'cancelled'));

                ALTER TABLE batch_items
                    ADD CONSTRAINT batch_items_purchased_qty_positive
                    CHECK (purchased_qty > 0),
                    ADD CONSTRAINT batch_items_unit_cost_non_negative
                    CHECK (unit_cost >= 0);

                ALTER TABLE inventory_balances
                    ADD CONSTRAINT inventory_balances_qty_available_non_negative
                    CHECK (qty_available >= 0);

                ALTER TABLE stock_movements
                    ADD CONSTRAINT stock_movements_qty_delta_non_zero
                    CHECK (qty_delta <> 0),
                    ADD CONSTRAINT stock_movements_type_check
                    CHECK (movement_type IN ('purchase_in', 'provider_refund_out', 'sale_out', 'client_refund_in', 'manual_adjustment'));
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('batch_items');
        Schema::dropIfExists('batches');
    }
};
