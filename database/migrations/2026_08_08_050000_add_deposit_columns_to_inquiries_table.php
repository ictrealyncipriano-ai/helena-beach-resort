<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('discount_amount');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('deposit_amount');
            $table->dateTime('deposit_paid_at')->nullable()->after('amount_paid');
            $table->dateTime('fully_paid_at')->nullable()->after('deposit_paid_at');
            $table->decimal('payment_pending_amount', 10, 2)->nullable()->after('fully_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['payment_pending_amount', 'fully_paid_at', 'deposit_paid_at', 'amount_paid', 'deposit_amount']);
        });
    }
};