<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->index(['purchased_at', 'id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['storage_id', 'occurred_at']);
        });

        Schema::table('purchase_refund_items', function (Blueprint $table) {
            $table->index(['product_id', 'batch_item_id']);
        });

        Schema::table('client_refund_items', function (Blueprint $table) {
            $table->index(['product_id', 'batch_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('client_refund_items', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'batch_item_id']);
        });

        Schema::table('purchase_refund_items', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'batch_item_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['storage_id', 'occurred_at']);
        });

        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex(['purchased_at', 'id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
        });
    }
};
