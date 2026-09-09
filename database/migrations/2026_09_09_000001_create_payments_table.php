<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * WP-1: additive payment ledger. Inquiry remains the booking record;
     * payments become the financial record. All columns nullable-safe and
     * portable across sqlite/mysql/pgsql (no CHECK constraints here).
     *
     * Dual-write phase: existing inquiries.* payment summary columns are
     * kept and written alongside this table. Nothing reads the ledger as
     * source-of-truth yet — that comes in later phases.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->string('provider')->default('paymongo')->index();
            $table->string('provider_payment_id')->nullable();
            $table->string('provider_checkout_id')->nullable();
            $table->string('provider_refund_id')->nullable();
            $table->string('method')->nullable();
            $table->string('type')->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('PHP');
            $table->string('status')->default('paid')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Nullable unique: multiple NULLs allowed on sqlite/mysql/pgsql,
            // so manual payments (no provider id) never collide while each
            // PayMongo payment can appear at most once (idempotency).
            $table->unique('provider_payment_id');
            $table->index(['inquiry_id', 'status']);
            $table->index('provider_checkout_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
