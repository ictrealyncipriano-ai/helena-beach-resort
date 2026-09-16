<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Services\CancellationPolicy;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Money Migration — quote golden characterization for
 * CancellationPolicy::quote().
 *
 * Captures the legacy binary-float outputs BEFORE migrating result() to
 * Money::mulPct()/sub(). Every absolute golden below was captured from the
 * legacy implementation on this runtime — not inferred. Tier selection,
 * hour math, and return shape are pinned alongside the money values.
 *
 * Captured finding: on this runtime legacy round() agrees with
 * Money::mulPct()/sub() across the whole probed domain, INCLUDING exact
 * .xx5 halves (2500.01 x 50% -> 1250.01, 5.35 x 50% -> 2.68). The
 * migration therefore preserves behavior on the reachable domain while
 * removing the platform-sensitive float path. The 100% tier is an exact
 * passthrough; the 0% tier refunds nothing and forfeits everything.
 */
class CancellationQuoteCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function booking(string $paid, ?string $checkIn): Inquiry
    {
        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Quote Guest',
            'email' => uniqid('qq').'@example.com',
            'phone' => '09170000000',
            'check_in' => $checkIn,
            'check_out' => '2026-10-20',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => Inquiry::STATUS_CONFIRMED,
            'total_amount' => '5000.00',
            'amount_paid' => $paid,
            'source' => Inquiry::SOURCE_WEBSITE,
        ]);
    }

    private function farOut(): string
    {
        return '2026-10-10';
    }

    private function midWindow(): string
    {
        // Frozen test now is 2026-08-15 12:00; 4 days out lands the 72h tier.
        return now()->addDays(4)->toDateString();
    }

    public function test_full_refund_tier_is_exact_passthrough(): void
    {
        $quote = CancellationPolicy::quote($this->booking('5000.00', $this->farOut()));

        $this->assertSame(100, $quote['pct']);
        $this->assertSame('5000.00', $quote['collected']);
        $this->assertSame('5000.00', $quote['refund_amount']);
        $this->assertSame('0.00', $quote['forfeit_amount']);
    }

    public function test_full_refund_tier_preserves_odd_cents(): void
    {
        $quote = CancellationPolicy::quote($this->booking('1234.56', $this->farOut()));

        $this->assertSame(100, $quote['pct']);
        $this->assertSame('1234.56', $quote['refund_amount']);
        $this->assertSame('0.00', $quote['forfeit_amount']);
    }

    public function test_half_refund_tier_splits_evenly(): void
    {
        $quote = CancellationPolicy::quote($this->booking('5000.00', $this->midWindow()));

        $this->assertSame(50, $quote['pct']);
        $this->assertSame('5000.00', $quote['collected']);
        $this->assertSame('2500.00', $quote['refund_amount']);
        $this->assertSame('2500.00', $quote['forfeit_amount']);
    }

    /**
     * Exact .xx5 halves, captured: legacy round() yields the half-up
     * outcomes below on this runtime, in agreement with Money. These
     * goldens stay green through the mulPct migration.
     *
     * @dataProvider halfCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('halfCases')]
    public function test_half_refund_tier_exact_halves(string $paid, string $refund, string $forfeit): void
    {
        $quote = CancellationPolicy::quote($this->booking($paid, $this->midWindow()));

        $this->assertSame(50, $quote['pct']);
        $this->assertSame($refund, $quote['refund_amount']);
        $this->assertSame($forfeit, $quote['forfeit_amount']);
        $this->assertSame($refund, Money::mulPct($paid, 50));
        $this->assertSame($forfeit, Money::sub($paid, $refund));
    }

    public static function halfCases(): array
    {
        return [
            'exact .xx5 half' => ['2500.01', '1250.01', '1250.00'],
            'repeating third' => ['333.33', '166.67', '166.66'],
            'classic half pattern' => ['5.35', '2.68', '2.67'],
            'small half' => ['1.15', '0.58', '0.57'],
            'sub-peso' => ['0.05', '0.03', '0.02'],
            'top-heavy' => ['4999.99', '2500.00', '2499.99'],
            'tiny remainder' => ['0.03', '0.02', '0.01'],
        ];
    }

    public function test_zero_refund_tier_forfeits_everything(): void
    {
        $checkIn = now()->addHours(10)->toDateString();
        $quote = CancellationPolicy::quote($this->booking('2000.00', $checkIn));

        $this->assertSame(0, $quote['pct']);
        $this->assertSame('0.00', $quote['refund_amount']);
        $this->assertSame('2000.00', $quote['forfeit_amount']);
    }

    public function test_past_check_in_refunds_nothing(): void
    {
        $quote = CancellationPolicy::quote($this->booking('1000.00', now()->subDay()->toDateString()));

        $this->assertSame(0, $quote['pct']);
        $this->assertSame('0.00', $quote['refund_amount']);
        $this->assertSame('1000.00', $quote['forfeit_amount']);
    }

    public function test_unpaid_booking_refunds_zero(): void
    {
        $quote = CancellationPolicy::quote($this->booking('0.00', $this->farOut()));

        $this->assertSame('0.00', $quote['collected']);
        $this->assertSame('0.00', $quote['refund_amount']);
        $this->assertSame('0.00', $quote['forfeit_amount']);
    }

    public function test_missing_check_in_refunds_nothing_but_reports_collected(): void
    {
        $quote = CancellationPolicy::quote($this->booking('1000.00', null));

        $this->assertSame(0, $quote['pct']);
        $this->assertSame('1000.00', $quote['collected']);
        $this->assertSame('0.00', $quote['refund_amount']);
        $this->assertSame('1000.00', $quote['forfeit_amount']);
    }
}
