<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('paymongo_payment_id')->nullable()->after('paymongo_session_id');
            $table->dateTime('refunded_at')->nullable()->after('payment_failed_at');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn([
                'paymongo_payment_id',
                'refunded_at',
                'refund_amount',
            ]);
        });
    }
};
