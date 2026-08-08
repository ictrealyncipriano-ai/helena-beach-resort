<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cottages', function (Blueprint $table) {
            $table->date('peak_start')->nullable()->after('rate_overnight');
            $table->date('peak_end')->nullable()->after('peak_start');
            $table->decimal('peak_rate_daytour', 10, 2)->nullable()->after('peak_end');
            $table->decimal('peak_rate_overnight', 10, 2)->nullable()->after('peak_rate_daytour');
        });
    }

    public function down(): void
    {
        Schema::table('cottages', function (Blueprint $table) {
            $table->dropColumn(['peak_rate_overnight', 'peak_rate_daytour', 'peak_end', 'peak_start']);
        });
    }
};