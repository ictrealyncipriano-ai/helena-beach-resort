<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE cottages ALTER COLUMN is_available DROP DEFAULT');
            DB::statement('ALTER TABLE cottages ALTER COLUMN is_available TYPE integer USING (is_available::integer)');
            DB::statement('ALTER TABLE cottages ALTER COLUMN is_available SET DEFAULT 1');
            DB::statement('ALTER TABLE galleries ALTER COLUMN is_active DROP DEFAULT');
            DB::statement('ALTER TABLE galleries ALTER COLUMN is_active TYPE integer USING (is_active::integer)');
            DB::statement('ALTER TABLE galleries ALTER COLUMN is_active SET DEFAULT 1');
            DB::statement('ALTER TABLE cottage_photos ALTER COLUMN is_primary DROP DEFAULT');
            DB::statement('ALTER TABLE cottage_photos ALTER COLUMN is_primary TYPE integer USING (is_primary::integer)');
            DB::statement('ALTER TABLE cottage_photos ALTER COLUMN is_primary SET DEFAULT 0');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE cottages ALTER COLUMN is_available TYPE boolean USING (is_available::boolean)');
            DB::statement('ALTER TABLE cottages ALTER COLUMN is_available SET DEFAULT true');
            DB::statement('ALTER TABLE galleries ALTER COLUMN is_active TYPE boolean USING (is_active::boolean)');
            DB::statement('ALTER TABLE galleries ALTER COLUMN is_active SET DEFAULT true');
            DB::statement('ALTER TABLE cottage_photos ALTER COLUMN is_primary TYPE boolean USING (is_primary::boolean)');
            DB::statement('ALTER TABLE cottage_photos ALTER COLUMN is_primary SET DEFAULT false');
        }
    }
};
