<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft deletes to inquiries and guests. Admin delete actions become
     * soft deletes so booking history stays auditable and portal/admin lookups
     * automatically exclude trashed rows via the SoftDeletes default scope.
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
