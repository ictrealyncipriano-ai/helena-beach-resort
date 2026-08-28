<?php

namespace Tests\Unit\Models;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Guest;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8.4/8.9 — direct coverage of the shared query scopes and the payment
 * helpers on the Inquiry (and related) models.
 */
class InquiryScopesAndPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function makeInquiry(string $reference, string $status): Inquiry
    {
        return Inquiry::create([
            'reference_code' => $reference,
            'name' => 'Guest',
            'email' => strtolower($reference).'@example.com',
            'status' => $status,
            'source' => Inquiry::SOURCE_WEBSITE,
        ]);
    }

    public function test_status_scopes_filter_correctly(): void
    {
        $this->makeInquiry('HB-SC-PEND', Inquiry::STATUS_PENDING);
        $this->makeInquiry('HB-SC-CONF', Inquiry::STATUS_CONFIRMED);
        $this->makeInquiry('HB-SC-CANC', Inquiry::STATUS_CANCELLED);
        $this->makeInquiry('HB-SC-EXP', Inquiry::STATUS_EXPIRED);

        $this->assertSame(1, Inquiry::pending()->count());
        $this->assertSame(1, Inquiry::confirmed()->count());
        $this->assertSame(1, Inquiry::cancelled()->count());
        $this->assertSame(1, Inquiry::expired()->count());
    }

    public function test_cottage_scope_available_returns_only_available(): void
    {
        // The seeded cottages cover available; add one explicitly unavailable.
        Cottage::create([
            'name' => 'Unavailable Hut',
            'capacity' => 4,
            'rate_daytour' => 1000,
            'rate_overnight' => 2000,
            'is_available' => false,
            'sort_order' => 99,
        ]);

        $available = Cottage::available()->get();

        $this->assertGreaterThan(0, $available->count());
        $this->assertFalse($available->contains(fn ($c) => ! $c->is_available));
    }

    public function test_date_block_scope_future_excludes_past(): void
    {
        $cottage = Cottage::first();

        CottageDateBlock::create(['cottage_id' => $cottage->id, 'date' => now()->subDay()->toDateString(), 'reason' => 'Manual']);
        CottageDateBlock::create(['cottage_id' => $cottage->id, 'date' => now()->addDay()->toDateString(), 'reason' => 'Manual']);

        $future = CottageDateBlock::future()->get()->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))->all();

        $this->assertSame([now()->addDay()->toDateString()], $future);
    }

    public function test_reverse_stay_decrements_guest_count(): void
    {
        $guest = Guest::create(['name' => 'Guest', 'email' => 'stay@example.com', 'total_stays' => 3]);
        $inquiry = $this->makeInquiry('HB-RS', Inquiry::STATUS_CONFIRMED);
        $inquiry->guest()->associate($guest)->save();

        $inquiry->reverseStay();

        $this->assertSame(2, $guest->fresh()->total_stays);
    }

    public function test_reverse_stay_never_goes_below_zero(): void
    {
        $guest = Guest::create(['name' => 'Guest', 'email' => 'zero@example.com', 'total_stays' => 0]);
        $inquiry = $this->makeInquiry('HB-ZERO', Inquiry::STATUS_CONFIRMED);
        $inquiry->guest()->associate($guest)->save();

        $inquiry->reverseStay();

        $this->assertSame(0, $guest->fresh()->total_stays);
    }

    public function test_collected_amount_only_sums_amount_paid(): void
    {
        $inquiry = $this->makeInquiry('HB-PAY', Inquiry::STATUS_CONFIRMED);
        $inquiry->update([
            'total_amount' => '5000.00',
            'deposit_amount' => '1500.00',
            'amount_paid' => '1500.00',
        ]);

        $this->assertSame('1500.00', $inquiry->collectedAmount());
        // A deposit alone is not "fully paid" once any amount is collected.
        $this->assertFalse($inquiry->isPaid());
    }

    public function test_has_payments_reflects_collected_amount(): void
    {
        $unpaid = $this->makeInquiry('HB-NOPAY', Inquiry::STATUS_CONFIRMED);
        $this->assertFalse($unpaid->hasPayments());

        $paid = $this->makeInquiry('HB-SOMEPAY', Inquiry::STATUS_CONFIRMED);
        $paid->update(['amount_paid' => '100.00']);
        $this->assertTrue($paid->fresh()->hasPayments());
    }
}
