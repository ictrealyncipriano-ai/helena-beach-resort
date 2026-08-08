<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('payment_proof_path')->nullable();
            $table->string('payment_proof_status')->default('none');
            $table->timestamp('payment_proof_submitted_at')->nullable();
            $table->timestamp('payment_proof_reviewed_at')->nullable();
            $table->string('payment_proof_review_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn([
                'payment_proof_path',
                'payment_proof_status',
                'payment_proof_submitted_at',
                'payment_proof_reviewed_at',
                'payment_proof_review_note',
            ]);
        });
    }
};
