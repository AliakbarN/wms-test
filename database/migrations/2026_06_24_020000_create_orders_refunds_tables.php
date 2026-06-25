<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->timestamp('refunded_at');
            $table->text('reason')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('purchase_refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_refund_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->restrictOnDelete();
            $table->integer('qty');
            $table->decimal('unit_refund_cost', 14, 2);
            $table->timestamps();

            $table->index('batch_item_id');
        });

        Schema::create('client_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->nullable()->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->timestamp('ordered_at');
            $table->string('status');
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('client_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('client_orders')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('requested_qty');
            $table->decimal('unit_sale_price', 14, 2);
            $table->timestamps();

            $table->index('order_id');
        });

        Schema::create('client_order_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('client_order_items')->restrictOnDelete();
            $table->foreignId('batch_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->restrictOnDelete();
            $table->integer('qty');
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('unit_sale_price', 14, 2);
            $table->timestamps();

            $table->index('order_item_id');
            $table->index('batch_item_id');
            $table->index('product_id');
            $table->index('storage_id');
        });

        Schema::create('client_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('client_orders')->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->timestamp('refunded_at');
            $table->text('reason')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('client_refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_refund_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_allocation_id')->constrained('client_order_allocations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('storage_id')->constrained()->restrictOnDelete();
            $table->integer('qty');
            $table->decimal('unit_sale_price', 14, 2);
            $table->decimal('unit_cost', 14, 2);
            $table->boolean('restock')->default(true);
            $table->timestamps();

            $table->index('order_allocation_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                ALTER TABLE purchase_refund_items
                    ADD CONSTRAINT purchase_refund_items_qty_positive CHECK (qty > 0),
                    ADD CONSTRAINT purchase_refund_items_cost_non_negative CHECK (unit_refund_cost >= 0);

                ALTER TABLE client_orders
                    ADD CONSTRAINT client_orders_status_check
                    CHECK (status IN ('confirmed', 'partially_refunded', 'fully_refunded', 'cancelled'));

                ALTER TABLE client_order_items
                    ADD CONSTRAINT client_order_items_qty_positive CHECK (requested_qty > 0),
                    ADD CONSTRAINT client_order_items_price_non_negative CHECK (unit_sale_price >= 0);

                ALTER TABLE client_order_allocations
                    ADD CONSTRAINT client_order_allocations_qty_positive CHECK (qty > 0),
                    ADD CONSTRAINT client_order_allocations_cost_non_negative CHECK (unit_cost >= 0),
                    ADD CONSTRAINT client_order_allocations_price_non_negative CHECK (unit_sale_price >= 0);

                ALTER TABLE client_refund_items
                    ADD CONSTRAINT client_refund_items_qty_positive CHECK (qty > 0),
                    ADD CONSTRAINT client_refund_items_cost_non_negative CHECK (unit_cost >= 0),
                    ADD CONSTRAINT client_refund_items_price_non_negative CHECK (unit_sale_price >= 0);
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_refund_items');
        Schema::dropIfExists('client_refunds');
        Schema::dropIfExists('client_order_allocations');
        Schema::dropIfExists('client_order_items');
        Schema::dropIfExists('client_orders');
        Schema::dropIfExists('purchase_refund_items');
        Schema::dropIfExists('purchase_refunds');
    }
};
