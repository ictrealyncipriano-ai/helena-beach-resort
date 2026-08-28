<?php

namespace Tests\Unit\Concerns;

use App\Exceptions\BookingConflictException;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8.9 — the Inquiry model's reservation-block behaviour moved into the
 * ManagesDateBlocks concern. These tests exercise that behaviour directly.
 */
class ManagesDateBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cottage(): Cottage
    {
        return Cottage::first();
    }

    private function inquiry(string $reference, int $cottageId, string $checkIn, ?string $checkOut = null): Inquiry
    {
        return Inquiry::create([
            'reference_code' => $reference,
            'name' => 'Guest',
            'email' => strtolower($reference).'@example.com',
            'cottage_id' => $cottageId,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'source' => Inquiry::SOURCE_WEBSITE,
            'status' => Inquiry::STATUS_PENDING,
        ]);
    }

    public function test_reserve_blocks_creates_one_row_per_date_inclusive(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->inquiry('HB-RES1', $cottage->id, '2026-09-10', '2026-09-13');

        $inquiry->reserveBlocks();

        $dates = CottageDateBlock::where('inquiry_id', $inquiry->id)->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))->sort()->values()->all();

        $this->assertSame(['2026-09-10', '2026-09-11', '2026-09-12', '2026-09-13'], $dates);
        $this->assertSame("Pending: {$inquiry->reference_code}", CottageDateBlock::first()->reason);
    }

    public function test_reserve_blocks_day_tour_single_date(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->inquiry('HB-DAY', $cottage->id, '2026-09-20');

        $inquiry->reserveBlocks();

        $this->assertCount(1, CottageDateBlock::where('inquiry_id', $inquiry->id)->get());
    }

    public function test_reserve_blocks_noop_without_cottage_or_checkin(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => 'HB-NODATE',
            'name' => 'Guest',
            'email' => 'nodate@example.com',
            'source' => Inquiry::SOURCE_WEBSITE,
        ]);

        // Must not throw and must not create any rows.
        $inquiry->reserveBlocks();

        $this->assertCount(0, CottageDateBlock::all());
    }

    public function test_reserve_blocks_throws_when_date_already_held(): void
    {
        $cottage = $this->cottage();
        $this->inquiry('HB-HOLDER', $cottage->id, '2026-10-01', '2026-10-02')->reserveBlocks();
        $competitor = $this->inquiry('HB-RIVAL', $cottage->id, '2026-10-02', '2026-10-03');

        $this->expectException(BookingConflictException::class);
        $competitor->reserveBlocks();
    }

    public function test_book_blocks_promotes_pending_to_booked_in_place(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->inquiry('HB-PROMO', $cottage->id, '2026-11-01', '2026-11-02');
        $inquiry->reserveBlocks();

        $inquiry->bookBlocks();

        $this->assertSame(
            'Booked: HB-PROMO',
            CottageDateBlock::where('inquiry_id', $inquiry->id)->first()->reason
        );
        $this->assertCount(2, CottageDateBlock::where('inquiry_id', $inquiry->id)->get(), 'Self-owned blocks are promoted, not duplicated.');
    }

    public function test_book_blocks_throws_when_date_held_by_another_inquiry(): void
    {
        $cottage = $this->cottage();
        $this->inquiry('HB-OTHER', $cottage->id, '2026-12-01', '2026-12-02')->bookBlocks();
        $competitor = $this->inquiry('HB-COMP', $cottage->id, '2026-12-02', '2026-12-03');

        $this->expectException(BookingConflictException::class);
        $competitor->bookBlocks();
    }

    public function test_release_blocks_deletes_this_inquiry_rows(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->inquiry('HB-REL', $cottage->id, '2026-09-10', '2026-09-12');
        $inquiry->reserveBlocks();

        $inquiry->releaseBlocks();

        $this->assertCount(0, CottageDateBlock::where('inquiry_id', $inquiry->id)->get());
    }

    public function test_release_blocks_original_schedule_ignores_current(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->inquiry('HB-ORIG', $cottage->id, '2026-09-10', '2026-09-12');
        $inquiry->reserveBlocks();

        // Simulate a modification: new dates held, old dates still blocked.
        $this->inquiry('HB-SECOND', $cottage->id, '2026-10-05', '2026-10-06')->reserveBlocks();

        $inquiry->releaseBlocks([
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-12',
        ]);

        $this->assertCount(0, CottageDateBlock::where('inquiry_id', $inquiry->id)->get());
    }

    public function test_release_blocks_releases_legacy_reason_rows(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->inquiry('HB-LEGACY', $cottage->id, '2026-09-01', '2026-09-02');
        $inquiry->reserveBlocks();

        // Simulate pre-inquiry_id legacy rows: drop the FK but keep the reason.
        CottageDateBlock::where('inquiry_id', $inquiry->id)->update(['inquiry_id' => null]);

        $inquiry->releaseBlocks();

        $this->assertCount(0, CottageDateBlock::whereDate('date', '>=', '2026-09-01')->whereDate('date', '<=', '2026-09-02')->where('cottage_id', $cottage->id)->get());
    }
}
