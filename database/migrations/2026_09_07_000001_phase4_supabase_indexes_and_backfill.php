<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4 — Supabase (Postgres) indexes, CHECKs, and verified
     * blocks.inquiry_id backfill. All statements are idempotent
     * (IF NOT EXISTS / catalog guards) so reruns are safe.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $indexes = [
            "CREATE INDEX IF NOT EXISTS inquiries_email_reference_code_idx ON inquiries (email, reference_code)",
            "CREATE INDEX IF NOT EXISTS inquiries_email_status_cottage_booking_checkin_created_idx ON inquiries (email, status, cottage_id, booking_type, check_in, created_at)",
            "CREATE INDEX IF NOT EXISTS inquiries_status_deposit_paid_at_idx ON inquiries (status, deposit_paid_at)",
            "CREATE INDEX IF NOT EXISTS inquiries_status_amount_paid_idx ON inquiries (status, amount_paid)",
            "CREATE INDEX IF NOT EXISTS inquiries_deposit_paid_at_idx ON inquiries (deposit_paid_at)",
            "CREATE INDEX IF NOT EXISTS inquiries_fully_paid_at_idx ON inquiries (fully_paid_at)",
            "CREATE INDEX IF NOT EXISTS inquiries_paymongo_session_id_idx ON inquiries (paymongo_session_id)",
            "CREATE INDEX IF NOT EXISTS guests_deleted_at_idx ON guests (deleted_at)",
            "CREATE UNIQUE INDEX IF NOT EXISTS guests_email_normalized_unique ON guests (LOWER(TRIM(email)))",
            "CREATE INDEX IF NOT EXISTS cottages_available_sort_idx ON cottages (is_available, sort_order)",
            "CREATE INDEX IF NOT EXISTS posts_active_published_idx ON posts (is_active, published_at)",
            "CREATE INDEX IF NOT EXISTS testimonials_active_sort_idx ON testimonials (is_active, sort_order)",
            "CREATE INDEX IF NOT EXISTS faqs_active_sort_idx ON faqs (is_active, sort_order)",
            "CREATE INDEX IF NOT EXISTS services_active_sort_idx ON services (is_active, sort_order)",
            "CREATE INDEX IF NOT EXISTS galleries_active_sort_idx ON galleries (is_active, sort_order)",
            "CREATE INDEX IF NOT EXISTS cottage_photos_cottage_primary_idx ON cottage_photos (cottage_id, is_primary)",
            "CREATE INDEX IF NOT EXISTS cottage_photos_cottage_sort_idx ON cottage_photos (cottage_id, sort_order)",
            "CREATE INDEX IF NOT EXISTS blocks_cottage_date_inquiry_idx ON cottage_date_blocks (cottage_id, date, inquiry_id)",
            "CREATE INDEX IF NOT EXISTS blocks_inquiry_date_idx ON cottage_date_blocks (inquiry_id, date)",
            "CREATE INDEX IF NOT EXISTS activity_logs_user_created_idx ON activity_logs (user_id, created_at)",
            "CREATE INDEX IF NOT EXISTS users_role_idx ON users (role)",
        ];
        foreach ($indexes as $sql) {
            DB::statement($sql);
        }

        $checks = [
            'cottages_peak_window_check' => "ALTER TABLE cottages ADD CONSTRAINT cottages_peak_window_check CHECK (peak_start IS NULL OR peak_end IS NULL OR peak_start <= peak_end)",
            'cottages_rates_nonnegative_check' => "ALTER TABLE cottages ADD CONSTRAINT cottages_rates_nonnegative_check CHECK (COALESCE(rate_daytour, 0) >= 0 AND COALESCE(rate_overnight, 0) >= 0 AND COALESCE(peak_rate_daytour, 0) >= 0 AND COALESCE(peak_rate_overnight, 0) >= 0)",
            'users_role_check' => "ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin','admin','staff'))",
        ];
        foreach ($checks as $name => $sql) {
            $exists = DB::select("SELECT 1 FROM pg_constraint WHERE conname = ?", [$name]);
            if (empty($exists)) {
                DB::statement($sql);
            }
        }

        // Deterministic backfill: reason "Pending|Booked: HB-XXX" -> reference_code,
        // accepted only on exact cottage match + date inside stay range.
        // Manual admin blocks (NULL reason, no HB code) and orphans stay NULL.
        $candidates = DB::selectOne("
            SELECT COUNT(*) AS c FROM cottage_date_blocks
            WHERE inquiry_id IS NULL AND reason ~ '^(Pending|Booked):\\s*HB-[A-Z0-9]+\\s*$'
        ");
        Log::info('Phase4 backfill candidates: '.($candidates->c ?? 0));

        DB::statement("
            UPDATE cottage_date_blocks b
            SET inquiry_id = i.id, updated_at = NOW()
            FROM inquiries i
            WHERE b.inquiry_id IS NULL
              AND b.reason ~ '^(Pending|Booked):\\s*HB-[A-Z0-9]+\\s*$'
              AND i.reference_code = TRIM(BOTH ' ' FROM REGEXP_REPLACE(b.reason, '^(Pending|Booked):\\s*(HB-[A-Z0-9]+)\\s*$', '\\2'))
              AND b.cottage_id = i.cottage_id
              AND b.date BETWEEN i.check_in AND COALESCE(i.check_out, i.check_in)
        ");

        $remaining = DB::selectOne("
            SELECT COUNT(*) AS c FROM cottage_date_blocks b
            WHERE b.inquiry_id IS NULL
              AND b.reason ~ '^(Pending|Booked):\\s*HB-[A-Z0-9]+\\s*$'
              AND EXISTS (
                SELECT 1 FROM inquiries i
                WHERE i.reference_code = TRIM(BOTH ' ' FROM REGEXP_REPLACE(b.reason, '^(Pending|Booked):\\s*(HB-[A-Z0-9]+)\\s*$', '\\2'))
                  AND b.cottage_id = i.cottage_id
                  AND b.date BETWEEN i.check_in AND COALESCE(i.check_out, i.check_in)
              )
        ");
        Log::info('Phase4 backfill remaining (should be 0): '.($remaining->c ?? '?'));

        $orphans = DB::selectOne("
            SELECT COUNT(*) AS c FROM cottage_date_blocks b
            WHERE b.inquiry_id IS NULL
              AND b.reason ~ '^(Pending|Booked):\\s*HB-[A-Z0-9]+\\s*$'
              AND NOT EXISTS (
                SELECT 1 FROM inquiries i
                WHERE i.reference_code = TRIM(BOTH ' ' FROM REGEXP_REPLACE(b.reason, '^(Pending|Booked):\\s*(HB-[A-Z0-9]+)\\s*$', '\\2'))
              )
        ");
        Log::info('Phase4 orphan HB blocks (left NULL): '.($orphans->c ?? 0));
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        foreach ([
            'inquiries_email_reference_code_idx',
            'inquiries_email_status_cottage_booking_checkin_created_idx',
            'inquiries_status_deposit_paid_at_idx',
            'inquiries_status_amount_paid_idx',
            'inquiries_deposit_paid_at_idx',
            'inquiries_fully_paid_at_idx',
            'inquiries_paymongo_session_id_idx',
            'guests_deleted_at_idx',
            'guests_email_normalized_unique',
            'cottages_available_sort_idx',
            'posts_active_published_idx',
            'testimonials_active_sort_idx',
            'faqs_active_sort_idx',
            'services_active_sort_idx',
            'galleries_active_sort_idx',
            'cottage_photos_cottage_primary_idx',
            'cottage_photos_cottage_sort_idx',
            'blocks_cottage_date_inquiry_idx',
            'blocks_inquiry_date_idx',
            'activity_logs_user_created_idx',
            'users_role_idx',
        ] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }

        foreach ([
            'cottages_peak_window_check' => 'cottages',
            'cottages_rates_nonnegative_check' => 'cottages',
            'users_role_check' => 'users',
        ] as $constraint => $table) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
        }

        // Backfilled inquiry_id values are data fixes and intentionally kept.
    }
};
