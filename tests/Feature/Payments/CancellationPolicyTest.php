<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use App\Services\CancellationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1.1: tiered cancellation quotes (share of collected, never total).
 * Default tiers locked: 168h/100, 72h/50, 24h/0.
 */
class CancellationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function booking(float $paid, string $checkIn): Inquiry
    {
        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Cancel Guest',
            'email' => uniqid('cx').'@example.com',
            'phone' => '09170000000',
            'check_in' => $checkIn,
            'check_out' => '2026-10-20',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => Inquiry::STATUS_CONFIRMED,
            'total_amount' => '5000.00',
            'amount_paid' => number_format($paid, 2, '.', ''),
            'source' => Inquiry::SOURCE_WEBSITE,
        ]);
    }

    public function test_full_refund_far_out(): void
    {
        $inquiry = $this->booking(5000, '2026-10-10');

        $quote = CancellationPolicy::quote($inquiry);

        $this->assertSame(100, $quote['pct']);
        $this->assertSame('5000.00', $quote['refund_amount']);
        $this->assertSame('0.00', $quote['forfeit_amount']);
    }

    public function test_half_refund_mid_window(): void
    {
        // now = 2026-08-15 12:00; 4 days out lands in the 72h tier.
        $checkIn = now()->addDays(4)->toDateString();
        $inquiry = $this->booking(5000, $checkIn);

        $quote = CancellationPolicy::quote($inquiry);

        $this->assertSame(50, $quote['pct']);
        $this->assertSame('2500.00', $quote['refund_amount']);
        $this->assertSame('2500.00', $quote['forfeit_amount']);
    }

    public function test_zero_refund_inside_24h(): void
    {
        $checkIn = now()->addHours(10)->toDateString();
        $inquiry = $this->booking(2000, $checkIn);

        $quote = CancellationPolicy::quote($inquiry);

        $this->assertSame(0, $quote['pct']);
        $this->assertSame('0.00', $quote['refund_amount']);
        $this->assertSame('2000.00', $quote['forfeit_amount']);
    }

    public function test_unpaid_booking_refunds_zero(): void
    {
        $inquiry = $this->booking(0, '2026-10-10');

        $quote = CancellationPolicy::quote($inquiry);

        $this->assertSame('0.00', $quote['refund_amount']);
    }

    public function test_invalid_json_falls_back_to_default(): void
    {
        SiteSetting::updateOrCreate(['key' => 'cancellation_policy_json'], ['value' => 'not-json', 'type' => 'textarea']);

        $inquiry = $this->booking(4000, '2026-10-10');
        $quote = CancellationPolicy::quote($inquiry);

        $this->assertSame(100, $quote['pct']);
        $this->assertSame('4000.00', $quote['refund_amount']);
    }
}
