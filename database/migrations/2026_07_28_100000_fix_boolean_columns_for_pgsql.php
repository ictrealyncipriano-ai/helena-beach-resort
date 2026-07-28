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
            DB::statement('ALTER TABLE testimonials ALTER COLUMN is_active DROP DEFAULT');
            DB::statement('ALTER TABLE testimonials ALTER COLUMN is_active TYPE integer USING (is_active::integer)');
            DB::statement('ALTER TABLE testimonials ALTER COLUMN is_active SET DEFAULT 1');

            DB::statement('ALTER TABLE faqs ALTER COLUMN is_active DROP DEFAULT');
            DB::statement('ALTER TABLE faqs ALTER COLUMN is_active TYPE integer USING (is_active::integer)');
            DB::statement('ALTER TABLE faqs ALTER COLUMN is_active SET DEFAULT 1');

            DB::statement('ALTER TABLE services ALTER COLUMN is_active DROP DEFAULT');
            DB::statement('ALTER TABLE services ALTER COLUMN is_active TYPE integer USING (is_active::integer)');
            DB::statement('ALTER TABLE services ALTER COLUMN is_active SET DEFAULT 1');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE testimonials ALTER COLUMN is_active TYPE boolean USING (is_active::boolean)');
            DB::statement('ALTER TABLE testimonials ALTER COLUMN is_active SET DEFAULT true');

            DB::statement('ALTER TABLE faqs ALTER COLUMN is_active TYPE boolean USING (is_active::boolean)');
            DB::statement('ALTER TABLE faqs ALTER COLUMN is_active SET DEFAULT true');

            DB::statement('ALTER TABLE services ALTER COLUMN is_active TYPE boolean USING (is_active::boolean)');
            DB::statement('ALTER TABLE services ALTER COLUMN is_active SET DEFAULT true');
        }
    }
};
