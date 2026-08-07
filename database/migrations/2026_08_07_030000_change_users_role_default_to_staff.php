<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Default new users to the least-privileged role. Existing rows are not
     * rewritten (only the column default changes), so seeded admins keep
     * their explicit role.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'staff'");
        } elseif ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('staff')->change();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('staff')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'admin'");
        } elseif ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('admin')->change();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('admin')->change();
            });
        }
    }
};
