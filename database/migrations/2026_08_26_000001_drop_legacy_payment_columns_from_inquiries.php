<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate historical data: copy paid_amount into amount_paid for
        // rows written before the deposit-aware columns existed.
        DB::table('inquiries')
            ->where('amount_paid', 0)
            ->where('paid_amount', '>', 0)
            ->update(['amount_paid' => DB::raw('paid_amount')]);

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['paid_at']);
            $table->dropColumn(['paid_at', 'paid_amount']);
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->after('total_amount');
            $table->decimal('paid_amount', 10, 2)->nullable()->after('paid_at');
            $table->index('paid_at');
        });
    }
};
