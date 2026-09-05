<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror pgsql CHECKs on MySQL 8.0.16+ (enforced). SQLite cannot add
     * CHECKs without a table rebuild, so it stays app-enforced there.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        foreach ([
            'inquiries_status_check' => "ALTER TABLE inquiries ADD CONSTRAINT inquiries_status_check CHECK (status IN ('pending','confirmed','cancelled','expired'))",
            'inquiries_booking_type_check' => "ALTER TABLE inquiries ADD CONSTRAINT inquiries_booking_type_check CHECK (booking_type IN ('day_tour','overnight'))",
            'inquiries_check_out_after_check_in_check' => 'ALTER TABLE inquiries ADD CONSTRAINT inquiries_check_out_after_check_in_check CHECK (check_out IS NULL OR check_in IS NULL OR check_out >= check_in)',
            'testimonials_rating_check' => 'ALTER TABLE testimonials ADD CONSTRAINT testimonials_rating_check CHECK (rating BETWEEN 1 AND 5)',
        ] as $name => $sql) {
            $exists = DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?",
                [$name]
            );
            if (empty($exists)) {
                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        foreach ([
            'inquiries_status_check' => 'inquiries',
            'inquiries_booking_type_check' => 'inquiries',
            'inquiries_check_out_after_check_in_check' => 'inquiries',
            'testimonials_rating_check' => 'testimonials',
        ] as $name => $table) {
            try {
                DB::statement("ALTER TABLE {$table} DROP CHECK {$name}");
            } catch (\Throwable $e) {
                // Constraint may not exist on older MySQL; ignore.
            }
        }
    }
};
