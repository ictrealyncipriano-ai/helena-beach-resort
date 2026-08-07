<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index the date-only range scans used by the public booking form and
     * cottage pages (where date >= today) — the composite (cottage_id, date)
     * unique index cannot serve a date-only predicate efficiently.
     */
    public function up(): void
    {
        Schema::table('cottage_date_blocks', function (Blueprint $table) {
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::table('cottage_date_blocks', function (Blueprint $table) {
            $table->dropIndex(['date']);
        });
    }
};
