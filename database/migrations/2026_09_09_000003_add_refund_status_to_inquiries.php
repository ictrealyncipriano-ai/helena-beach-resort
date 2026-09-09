<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WP-7: durable refund lifecycle on the booking. Previously a failed
     * online refund left no trace (flash warning only); now failures persist
     * as failed + attempts + last_error for the retry command and the admin
     * "requires attention" banner. Backfills completed for already-refunded
     * rows, none otherwise (failed/manual-required history is not
     * reconstructible — conservative default).
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('refund_status', 20)->default('none')->after('refund_amount');
            $table->unsignedInteger('refund_attempts')->default(0)->after('refund_status');
            $table->text('refund_last_error')->nullable()->after('refund_attempts');
        });

        DB::table('inquiries')->whereNotNull('refunded_at')->update(['refund_status' => 'completed']);
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['refund_status', 'refund_attempts', 'refund_last_error']);
        });
    }
};
