<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Money Migration — invoice subtotal golden characterization for
 * InvoiceController::buildLineItems().
 *
 * Captures the legacy binary-float summation outputs BEFORE migrating
 * line 99 (array_sum over (float) line totals) to exact Money::add().
 * Every absolute golden below was captured from the legacy implementation
 * on this runtime — not inferred.
 *
 * Captured finding: on this runtime legacy formatPrice(array_sum(...))
 * agrees with the exact Money::add() loop across the whole probed domain,
 * INCLUDING the classic 0.10+0.20 trap and exact .xx5 halves. The
 * migration therefore preserves behavior on the reachable domain while
 * removing the platform-sensitive float path. Per-line totals are already
 * exact (formatPrice -> Money::from); only the summation moves.
 */
class InvoiceSubtotalCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function peakCottage(): Cottage
    {
        return Cottage::create([
            'name' => 'Invoice Villa',
            'description' => 'Peak aware',
            'rate_daytour' => 1000,
            'rate_overnight' => 2000,
            'peak_start' => '2026-12-20',
            'peak_end' => '2027-01-05',
            'peak_rate_daytour' => 1500,
            'peak_rate_overnight' => 3000,
            'is_available' => true,
        ]);
    }

    private function bookedAndConfirmed(array $overrides = []): Inquiry
    {
        $this->post('/book', array_merge([
            'name' => 'Invoice Guest',
            'email' => 'invoice'.uniqid().'@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => $this->peakCottage()->id,
            'check_in' => '2026-12-24',
            'check_out' => '2026-12-26',
            'pax' => 2,
        ], $overrides));

        $email = $overrides['email'] ?? null;

        $inquiry = $email
            ? Inquiry::where('email', $email)->latest('id')->first()
            : Inquiry::latest('id')->first();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => ['token' => $inquiry->token, 'granted_at' => now()->toDateTimeString()]]];
    }

    /**
     * Exact replica of the legacy line 99 summation path.
     */
    private function legacySum(array $totals): string
    {
        return formatPrice(array_sum(array_map(fn ($t) => (float) $t, $totals)), 2, false);
    }

    /**
     * Exact replica of the planned Money::add() loop (also matches
     * PricingService::nightlyTotal() accumulation style).
     */
    private function moneySum(array $totals): string
    {
        $subtotal = '0.00';

        foreach ($totals as $t) {
            $subtotal = Money::add($subtotal, (string) $t);
        }

        return $subtotal;
    }

    public function test_peak_two_night_subtotal_is_exact(): void
    {
        $inquiry = $this->bookedAndConfirmed();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertViewHas('subtotal', '6000.00');
    }

    public function test_mixed_peak_regular_subtotal_is_exact(): void
    {
        $inquiry = $this->bookedAndConfirmed([
            'check_in' => '2027-01-04',
            'check_out' => '2027-01-07',
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertViewHas('subtotal', '8000.00');
    }

    public function test_day_tour_single_line_subtotal(): void
    {
        $inquiry = $this->bookedAndConfirmed([
            'booking_type' => 'day_tour',
            'check_in' => '2026-12-24',
            'check_out' => null,
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertViewHas('subtotal', '1500.00');
    }

    public function test_fallback_recorded_total_passes_through(): void
    {
        $cottage = $this->peakCottage();

        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Orphan Guest',
            'email' => 'orphan'.uniqid().'@example.com',
            'phone' => '09170000000',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'total_amount' => '5000.00',
            'source' => 'booking',
        ]);
        $cottage->delete();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry->refresh()))
            ->assertOk()
            ->assertViewHas('subtotal', '5000.00');
    }

    /**
     * Classic binary-float trap + multi-night sums, captured: legacy
     * agrees with Money on this runtime. These goldens stay green through
     * the Money::add() migration.
     *
     * @dataProvider sumCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('sumCases')]
    public function test_legacy_sum_agrees_with_money(array $totals, string $expected): void
    {
        $this->assertSame($expected, $this->legacySum($totals));
        $this->assertSame($expected, $this->moneySum($totals));
    }

    public static function sumCases(): array
    {
        return [
            'binary trap 0.10 + 0.20' => [['0.10', '0.20'], '0.30'],
            'two peak nights' => [['3000.00', '3000.00'], '6000.00'],
            'mixed peak + regular' => [['3000.00', '3000.00', '2000.00'], '8000.00'],
            'single day tour' => [['1500.00'], '1500.00'],
            'odd cents' => [['1234.56', '100.01'], '1334.57'],
            'many small lines' => [['0.07', '0.01', '0.02', '0.10'], '0.20'],
            'repeated 333.33' => [['333.33', '333.33', '333.33'], '999.99'],
        ];
    }

    /**
     * Exact .xx5 semantic boundary, captured: per-line half-up happens in
     * formatPrice (Money::from) before summation, so both paths below see
     * already-normalized 2-decimal inputs. Demonstrates the potential
     * boundary without assuming the runtime exhibits a divergence.
     *
     * @dataProvider halfCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('halfCases')]
    public function test_xx5_boundary_inputs_agree(string $rawRate, string $line, array $extra, string $expected): void
    {
        // Per-line normalization is already exact on both paths.
        $this->assertSame($line, formatPrice($rawRate, 2, false));
        $this->assertSame($line, Money::from($rawRate));

        $totals = array_merge([$line], $extra);

        $this->assertSame($expected, $this->legacySum($totals));
        $this->assertSame($expected, $this->moneySum($totals));
    }

    public static function halfCases(): array
    {
        return [
            'third-decimal 5 rounds line up' => ['10.005', '10.01', ['0.00'], '10.01'],
            'third-decimal 4 rounds line down' => ['10.004', '10.00', ['0.01'], '10.01'],
            'half-cent line in multi-night sum' => ['2500.005', '2500.01', ['2500.00'], '5000.01'],
            'sub-peso half' => ['0.005', '0.01', ['0.02'], '0.03'],
            'negative half line' => ['-10.005', '-10.01', ['10.01'], '0.00'],
        ];
    }
}
