<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->unsignedInteger('width')->nullable()->after('photo_path');
            $table->unsignedInteger('height')->nullable()->after('width');
        });

        Schema::table('cottage_photos', function (Blueprint $table) {
            $table->unsignedInteger('width')->nullable()->after('photo_path');
            $table->unsignedInteger('height')->nullable()->after('width');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('cover_width')->nullable()->after('cover_image');
            $table->unsignedInteger('cover_height')->nullable()->after('cover_width');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
        });

        Schema::table('cottage_photos', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['cover_width', 'cover_height']);
        });
    }
};
