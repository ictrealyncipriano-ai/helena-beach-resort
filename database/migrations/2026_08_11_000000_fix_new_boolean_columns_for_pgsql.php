<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Keeps the boolean-like flag columns on the tables added after the earlier
 * `fix_boolean_columns_for_pgsql` migrations consistent with the rest of the
 * schema on Postgres, where these flags are stored as integers (1/0) so the
 * framework's SQLite-derived integer boolean bindings compare cleanly.
 * On SQLite this is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['posts' => 'is_active', 'promo_codes' => 'is_active'] as $table => $column) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP DEFAULT");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE integer USING ({$column}::integer)");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT 1");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['posts' => 'is_active', 'promo_codes' => 'is_active'] as $table => $column) {
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE boolean USING ({$column}::boolean)");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT true");
        }
    }
};