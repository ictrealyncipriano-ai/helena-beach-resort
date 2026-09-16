<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Services\BookingModificationService;
use App\Services\InquiryService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Money Migration — modify-total golden characterization for
 * BookingModificationService::apply() lines 43-46.
 *
 * Captures the legacy binary-float recompute outputs BEFORE migrating
 * `number_format(max(0, (float) $newTotal - $discount))` to exact
 * `Money::from()/cmp()/sub()`. Every absolute golden below was captured
 * from the legacy implementation on this runtime — not inferred. Unlike
 * display/gate slices, the recomputed value is PERSISTED to
 * inquiries.total_amount, so each case asserts the refreshed database
 * row — legacy result, exact Money result, and stored value must be
 * identical.
 *
 * Captured finding: on this runtime legacy float math agrees with the
 * exact Money recompute across the reachable 2-decimal domain ($newTotal
 * is already an exact Money string via PricingService, discount_amount is
 * decimal:2). The migration therefore preserves the persisted total while
 * removing the float seam. The null-total no-op pins what the code
 * demonstrably does today: with no computable total the stored amount is
 * left untouched (HTTP validation normally prevents this; the direct
 * service call below proves the defensive branch).
 */
class ModifyTotalCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cottages(): array
    {
        return Cottage::available()->get()->all();
    }

    private function createBooking(string $email = 'modifytotal@example.com'): Inquiry
    {
        $cottage = $this->cottages()[0];
        $checkIn = now()->addDays(2);
        $checkOut = now()->addDays(4);

        $total = '0.00';
        for ($cursor = $checkIn->copy(); $cursor->lt($checkOut); $cursor->addDay()) {
            $total = number_format(
                (float) $total + (float) $cottage->rateFor($cursor->copy(), 'overnight'),
                2, '.', ''
            );
        }

        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Modify Guest',
            'email' => $email,
            'phone' => '09170000000',
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'cottage_id' => $cottage->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'pending',
            'source' => 'website',
            'total_amount' => $total,
        ]);

        $inquiry->reserveBlocks();

        return $inquiry;
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    private function validModifyPayload(array $overrides = []): array
    {
        return array_merge([
            'booking_type' => 'overnight',
            'cottage_id' => $this->cottages()[0]->id,
            'check_in' => now()->addDays(6)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
            'pax' => 4,
        ], $overrides);
    }

    /**
     * Exact replica of the legacy lines 44-45 recompute path.
     */
    private function legacyRecompute(string $newTotal, mixed $discount): string
    {
        return number_format(max(0, (float) $newTotal - (float) ($discount ?? 0)), 2, '.', '');
    }

    /**
     * Exact replica of the planned Money recompute (same clamp style as
     * PricingService::applyDiscount()).
     */
    private function moneyRecompute(string $newTotal, mixed $discount): string
    {
        $base = Money::from($newTotal);
        $taken = Money::from($discount ?? '0.00');

        return Money::cmp($base, $taken) < 0 ? '0.00' : Money::sub($base, $taken);
    }

    public function test_plain_modify_persists_identical_total(): void
    {
        $cottage = $this->cottages()[0];
        $inquiry = $this->createBooking('mtplain@example.com');
        $newCheckIn = now()->addDays(10)->toDateString();
        $newCheckOut = now()->addDays(12)->toDateString();

        $nightly = (float) $cottage->rateFor(now()->parse($newCheckIn), 'overnight');
        $newTotal = number_format($nightly * 2, 2, '.', '');

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload([
                'check_in' => $newCheckIn,
                'check_out' => $newCheckOut,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $persisted = $inquiry->refresh()->total_amount;

        $this->assertSame($this->legacyRecompute($newTotal, '0.00'), $persisted);
        $this->assertSame($this->moneyRecompute($newTotal, '0.00'), $persisted);
    }

    public function test_discounted_modify_persists_identical_total(): void
    {
        $inquiry = $this->createBooking('mtdisc@example.com');
        $inquiry->update(['discount_amount' => 1000, 'total_amount' => '4000.00']);
        $newCheckIn = now()->addDays(10)->toDateString();
        $newCheckOut = now()->addDays(12)->toDateString();

        $cottage = $this->cottages()[0];
        $nightly = (float) $cottage->rateFor(now()->parse($newCheckIn), 'overnight');
        $newTotal = number_format($nightly * 2, 2, '.', '');

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload([
                'check_in' => $newCheckIn,
                'check_out' => $newCheckOut,
            ]))
            ->assertRedirect();

        $persisted = $inquiry->refresh()->total_amount;

        $this->assertSame($this->legacyRecompute($newTotal, 1000), $persisted);
        $this->assertSame($this->moneyRecompute($newTotal, 1000), $persisted);
    }

    public function test_oversized_discount_clamps_persisted_total_at_zero(): void
    {
        $inquiry = $this->createBooking('mtclamp@example.com');
        $inquiry->update(['discount_amount' => 99999, 'total_amount' => '4000.00']);
        $newCheckIn = now()->addDays(10)->toDateString();
        $newCheckOut = now()->addDays(12)->toDateString();

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload([
                'check_in' => $newCheckIn,
                'check_out' => $newCheckOut,
            ]))
            ->assertRedirect();

        $this->assertSame('0.00', $inquiry->refresh()->total_amount);
    }

    public function test_null_total_leaves_persisted_total_unchanged(): void
    {
        // Defensive branch as demonstrably executed today: an overnight
        // modify with no check-out yields no computable total, so apply()
        // skips the recompute and the stored amount survives. Reached via a
        // direct service call because HTTP validation requires check_out.
        $inquiry = $this->createBooking('mtnull@example.com');
        $before = $inquiry->refresh()->total_amount;

        $validated = $this->validModifyPayload(['check_out' => null]);
        $original = [
            'cottage_id' => $inquiry->cottage_id,
            'check_in' => $inquiry->check_in?->format('Y-m-d'),
            'check_out' => $inquiry->check_out?->format('Y-m-d'),
            'booking_type' => $inquiry->booking_type,
        ];

        $this->assertNull(app(InquiryService::class)->calculateTotal([
            'booking_type' => $validated['booking_type'],
            'cottage_id' => $validated['cottage_id'],
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
        ]));

        app(BookingModificationService::class)->apply(
            $inquiry, $validated, $original, false, app(InquiryService::class)
        );

        $this->assertSame($before, $inquiry->refresh()->total_amount);
    }

    /**
     * Reachable 2-decimal recompute goldens, captured: legacy float math
     * agrees with the Money recompute on this runtime, including the
     * non-negative clamp. These stay green through the seam migration.
     *
     * @dataProvider recomputeCases
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('recomputeCases')]
    public function test_legacy_recompute_agrees_with_money(string $newTotal, mixed $discount, string $expected): void
    {
        $this->assertSame($expected, $this->legacyRecompute($newTotal, $discount));
        $this->assertSame($expected, $this->moneyRecompute($newTotal, $discount));
    }

    public static function recomputeCases(): array
    {
        return [
            'plain total' => ['6000.00', '0.00', '6000.00'],
            'null discount' => ['6000.00', null, '6000.00'],
            'with discount' => ['6000.00', '1000.00', '5000.00'],
            'discount equals total' => ['6000.00', '6000.00', '0.00'],
            'oversized discount clamps' => ['500.00', '1000.00', '0.00'],
            'zero stays zero' => ['0.00', '0.00', '0.00'],
            'odd cents' => ['4823.50', '123.50', '4700.00'],
        ];
    }
}
