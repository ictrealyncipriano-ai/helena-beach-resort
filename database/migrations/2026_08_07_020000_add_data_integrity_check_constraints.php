<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add CHECK constraints non-destructively. Mirrors the driver-guard
     * pattern from 2026_07_22_081746_fix_boolean_columns_for_pgsql.php:
     *
     *   - pgsql: ALTER TABLE ... ADD CONSTRAINT is fully supported.
     *   - sqlite: ALTER TABLE cannot add CHECK constraints to an existing
     *     table without a destructive table rebuild, so it is skipped.
     *   - mysql: older versions silently ignore CHECK, so it is skipped.
     *
     * The check_out constraint additionally allows check_out with a NULL
     * check_in so no pre-existing row can ever block the migration.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement(
            "ALTER TABLE inquiries ADD CONSTRAINT inquiries_status_check "
            ."CHECK (status IN ('pending','confirmed','cancelled','expired'))"
        );

        DB::statement(
            "ALTER TABLE inquiries ADD CONSTRAINT inquiries_booking_type_check "
            ."CHECK (booking_type IN ('day_tour','overnight'))"
        );

        DB::statement(
            'ALTER TABLE inquiries ADD CONSTRAINT inquiries_check_out_after_check_in_check '
            .'CHECK (check_out IS NULL OR check_in IS NULL OR check_out >= check_in)'
        );

        DB::statement(
            'ALTER TABLE testimonials ADD CONSTRAINT testimonials_rating_check '
            .'CHECK (rating BETWEEN 1 AND 5)'
        );
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE inquiries DROP CONSTRAINT IF EXISTS inquiries_status_check');
        DB::statement('ALTER TABLE inquiries DROP CONSTRAINT IF EXISTS inquiries_booking_type_check');
        DB::statement('ALTER TABLE inquiries DROP CONSTRAINT IF EXISTS inquiries_check_out_after_check_in_check');
        DB::statement('ALTER TABLE testimonials DROP CONSTRAINT IF EXISTS testimonials_rating_check');
    }
};
