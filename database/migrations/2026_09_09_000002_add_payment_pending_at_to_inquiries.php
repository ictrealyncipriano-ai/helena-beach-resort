<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WP-3: timestamp for when the current payment_pending_amount was set,
     * so abandoned checkouts can be aged out. Nullable for legacy rows
     * (which are treated as very old by the sweep).
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->timestamp('payment_pending_at')->nullable()->after('payment_pending_amount');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('payment_pending_at');
        });
    }
};
