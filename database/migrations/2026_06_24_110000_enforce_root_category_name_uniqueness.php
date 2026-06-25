<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE categories DROP CONSTRAINT categories_provider_id_parent_id_name_unique');
        DB::statement(<<<'SQL'
            ALTER TABLE categories
            ADD CONSTRAINT categories_provider_id_parent_id_name_unique
            UNIQUE NULLS NOT DISTINCT (provider_id, parent_id, name)
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE categories DROP CONSTRAINT categories_provider_id_parent_id_name_unique');
        DB::statement(<<<'SQL'
            ALTER TABLE categories
            ADD CONSTRAINT categories_provider_id_parent_id_name_unique
            UNIQUE (provider_id, parent_id, name)
            SQL);
    }
};
