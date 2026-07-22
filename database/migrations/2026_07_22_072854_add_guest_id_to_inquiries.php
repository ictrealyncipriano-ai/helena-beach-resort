<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('inquiries', 'guest_id')) {
            return;
        }

        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreignId('guest_id')
                ->nullable()
                ->constrained('guests')
                ->nullOnDelete()
                ->after('cottage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_id');
        });
    }
};
