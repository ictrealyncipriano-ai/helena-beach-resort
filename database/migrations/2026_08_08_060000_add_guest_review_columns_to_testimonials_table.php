<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->foreignId('inquiry_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->string('source')->default('admin');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inquiry_id');
            $table->dropColumn(['guest_email', 'source']);
        });
    }
};
