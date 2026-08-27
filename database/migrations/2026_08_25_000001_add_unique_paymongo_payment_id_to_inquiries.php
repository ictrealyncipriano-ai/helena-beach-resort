<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A PayMongo payment id may be recorded against at most one inquiry.
     * This is the last-resort constraint behind the webhook's locked
     * re-validation: even if two concurrent deliveries slip past the
     * idempotency checks, the second INSERT/UPDATE fails instead of
     * crediting the same money twice.
     */
    public function up(): void
    {
        $duplicates = DB::table('inquiries')
            ->select('paymongo_payment_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('paymongo_payment_id')
            ->groupBy('paymongo_payment_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            Log::error('Cannot add unique index on inquiries.paymongo_payment_id: duplicate payment ids found.', [
                'duplicates' => $duplicates->pluck('paymongo_payment_id')->all(),
            ]);

            throw new RuntimeException(
                'Duplicate paymongo_payment_id values exist on inquiries. Resolve them before migrating.'
            );
        }

        Schema::table('inquiries', function (Blueprint $table) {
            $table->unique('paymongo_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropUnique(['paymongo_payment_id']);
        });
    }
};
