<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slice 6 — portable query indexes on inquiries.
     *
     * Each index maps to a confirmed hot query path (stale-pending sweep,
     * failed-refund retry, expiry warnings, proof queue, session
     * reconcile). Schema-builder calls run identically on MySQL, SQLite
     * and pgsql — unlike the Phase-4 raw-SQL indexes, which are
     * pgsql-only (including the only paymongo_session_id index).
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->index(['status', 'payment_pending_at']);
            $table->index('refund_status');
            $table->index(['status', 'expiry_warned_at']);
            $table->index('payment_proof_status');
            $table->index('paymongo_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['status', 'payment_pending_at']);
            $table->dropIndex(['refund_status']);
            $table->dropIndex(['status', 'expiry_warned_at']);
            $table->dropIndex(['payment_proof_status']);
            $table->dropIndex(['paymongo_session_id']);
        });
    }
};
