<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Back the three most frequent inquiry access paths:
     *   - portal lookup:  WHERE email = ?
     *   - cron expiry:    WHERE status = 'pending' AND created_at <= ?
     *   - dashboard:      WHERE status = 'confirmed' AND check_in >= ?
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->index('email');
            $table->index(['status', 'created_at']);
            $table->index(['status', 'check_in']);
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['status', 'check_in']);
        });
    }
};
