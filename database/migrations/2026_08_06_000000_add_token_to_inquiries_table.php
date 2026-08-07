<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a non-enumerable booking token to every inquiry. Existing rows are
     * backfilled before the unique index is added so we never collide.
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->after('reference_code');
        });

        DB::table('inquiries')->orderBy('id')->select('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('inquiries')
                    ->where('id', $row->id)
                    ->update(['token' => bin2hex(random_bytes(20))]);
            }
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('token', 64)->nullable(false)->change();
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->unique('token');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn('token');
        });
    }
};
