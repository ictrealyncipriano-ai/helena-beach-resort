<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Services\BookingCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1.1/P1.3: the quoted tiered share persisted at cancel time stays
 * authoritative — a later retry path (refundFailed /
 * refundAlreadyProcessed) must not overwrite it with the full collected
 * amount.
 */
class CancelRefundAmountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_finalize_persists_quoted_share_and_retry_keeps_it(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Guest',
            'email' => 'quoteshare@example.com',
            'phone' => '09170000000',
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
            'status' => Inquiry::STATUS_CONFIRMED,
            'source' => 'website',
            'total_amount' => '5000.00',
            'amount_paid' => '5000.00',
            'fully_paid_at' => now(),
        ]);

        // Simulate the tiered refund that just succeeded (the claim sets
        // refunded_at before finalize runs).
        $inquiry->update(['refunded_at' => now()]);

        $service = app(BookingCancellationService::class);
        $service->finalizeCancellation($inquiry, true, ['refunded' => true, 'quote' => ['refund_amount' => '2500.00']]);

        $this->assertSame(Inquiry::STATUS_CANCELLED, $inquiry->refresh()->status);
        $this->assertSame('2500.00', $inquiry->refresh()->refund_amount);

        // P1.3 retry path: the refund was already processed — the persisted
        // quoted share must survive, not refundableAmount() (5000.00).
        $service->finalizeCancellation(
            $inquiry->refresh(),
            true,
            ['refunded' => false, 'refundAlreadyProcessed' => true, 'quote' => ['refund_amount' => '2500.00']]
        );

        $this->assertSame('2500.00', $inquiry->refresh()->refund_amount);
    }
}
