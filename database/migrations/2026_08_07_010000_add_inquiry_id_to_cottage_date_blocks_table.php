<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link every cottage date block to the inquiry that holds it. The FK is
     * cascadeOnDelete so a fully-removed inquiry (force delete) never leaks
     * orphaned blocks; soft deletes keep the inquiry row so blocks survive.
     */
    public function up(): void
    {
        Schema::table('cottage_date_blocks', function (Blueprint $table) {
            $table->unsignedBigInteger('inquiry_id')->nullable()->after('cottage_id');

            $table->foreign('inquiry_id')
                ->references('id')
                ->on('inquiries')
                ->cascadeOnDelete();

            $table->index('inquiry_id');
        });
    }

    public function down(): void
    {
        Schema::table('cottage_date_blocks', function (Blueprint $table) {
            $table->dropForeign(['inquiry_id']);
            $table->dropIndex(['inquiry_id']);
            $table->dropColumn('inquiry_id');
        });
    }
};
