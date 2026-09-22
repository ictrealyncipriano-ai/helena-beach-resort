<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slice 10 — portable normalized-email uniqueness on guests.
     *
     * Adds a stored generated email_normalized column (LOWER(TRIM(email)),
     * the exact normalization Guest::findByEmailOrCreate() already applies)
     * with a unique index, on every driver. The Phase-4 functional unique
     * index covers pgsql only; this covers MySQL and SQLite too.
     *
     * The duplicate guard runs BEFORE any schema change and aborts listing
     * every conflict: nothing is merged, re-linked, restored or deleted.
     * Resolve via `guests:email-conflicts`, then re-run.
     */
    public function up(): void
    {
        $conflicts = DB::table('guests')
            ->selectRaw('LOWER(TRIM(email)) as normalized, COUNT(*) as total')
            ->whereNotNull('email')
            ->groupBy('normalized')
            ->having('total', '>', 1)
            ->orderBy('normalized')
            ->get();

        if ($conflicts->isNotEmpty()) {
            $lines = $conflicts->map(fn ($row) => "  - '{$row->normalized}' ({$row->total} rows)")->join("\n");

            throw new RuntimeException(
                "Cannot add guests.email_normalized unique index: {$conflicts->count()} normalized duplicate(s) exist, resolve them first:\n{$lines}"
            );
        }

        Schema::table('guests', function (Blueprint $table) {
            $table->string('email_normalized')->storedAs('LOWER(TRIM(email))')->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique(['email_normalized']);
            $table->dropColumn('email_normalized');
        });
    }
};
