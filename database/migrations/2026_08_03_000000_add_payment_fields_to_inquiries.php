<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->after('total_amount');
            $table->decimal('paid_amount', 10, 2)->nullable()->after('paid_at');
            $table->string('payment_method')->nullable()->after('paid_amount');
            $table->string('paymongo_session_id')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn([
                'paid_at',
                'paid_amount',
                'payment_method',
                'paymongo_session_id',
            ]);
        });
    }
};
