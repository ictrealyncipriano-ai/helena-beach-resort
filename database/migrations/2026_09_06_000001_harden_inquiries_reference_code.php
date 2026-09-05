<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill any legacy NULL reference codes before enforcing NOT NULL.
        $rows = DB::table('inquiries')->whereNull('reference_code')->select('id')->get();
        foreach ($rows as $row) {
            DB::table('inquiries')->where('id', $row->id)->update([
                'reference_code' => 'HB-'.strtoupper(bin2hex(random_bytes(5))),
            ]);
        }

        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('reference_code', 20)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('reference_code', 20)->nullable()->change();
        });
    }
};
