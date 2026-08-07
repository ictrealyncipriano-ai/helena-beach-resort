<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index the FK columns that drive admin aggregations and the dashboard:
     *   - inquiries.cottage_id / guest_id: withCount + filter lookups
     *   - inquiries.paid_at:               paid-this-month + revenue series
     *   - inquiries.deleted_at:            soft-delete filter on every query
     *   - child FKs:                       eager-loaded relations on public pages
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->index('cottage_id');
            $table->index('guest_id');
            $table->index('paid_at');
            $table->index('deleted_at');
        });

        Schema::table('cottage_photos', function (Blueprint $table) {
            $table->index('cottage_id');
        });

        Schema::table('cottage_amenities', function (Blueprint $table) {
            $table->index('cottage_id');
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->index('cottage_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['cottage_id']);
            $table->dropIndex(['guest_id']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('cottage_photos', function (Blueprint $table) {
            $table->dropIndex(['cottage_id']);
        });

        Schema::table('cottage_amenities', function (Blueprint $table) {
            $table->dropIndex(['cottage_id']);
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropIndex(['cottage_id']);
        });
    }
};
