<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Services\BookingCancellationService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Item 6: BookingCancellationService::finalizeCancellation() runs the
 * status/refund update + block release + stay reversal in one transaction
 * against an explicit locked snapshot, so any failure rolls everything back.
 */
class CancellationAtomicityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function confirmedBookingWithBlocks(
        string $email,
        string $checkIn = '2026-09-01',
        string $checkOut = '2026-09-03',
        string $bookingType = 'overnight',
        int $guestStays = 0,
    ): Inquiry {
        $guest = Guest::create([
            'name' => 'Cancel Guest',
            'email' => $email,
            'total_stays' => $guestStays,
        ]);

        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Cancel Guest',
            'email' => $email,
            'phone' => '09170000000',
            'booking_type' => $bookingType,
            'cottage_id' => Cottage::first()->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'pax' => 2,
            'status' => Inquiry::STATUS_CONFIRMED,
            'source' => 'website',
            'total_amount' => '5000.00',
            'guest_id' => $guest->id,
        ]);

        $inquiry->bookBlocks();

        return $inquiry->refresh();
    }

    private function blockCount(Inquiry $inquiry): int
    {
        return CottageDateBlock::where('cottage_id', $inquiry->cottage_id)
            ->where('inquiry_id', $inquiry->id)
            ->count();
    }

    public function test_rollback_when_release_blocks_throws(): void
    {
        $inquiry = $this->confirmedBookingWithBlocks('rollback@example.com', guestStays: 2);

        $this->assertSame(2, $this->blockCount($inquiry));

        $service = new class(app(RefundService::class)) extends BookingCancellationService
        {
            protected function releaseInquiryBlocks(Inquiry $locked, array $original): void
            {
                throw new \RuntimeException('release failed');
            }
        };

        try {
            $service->finalizeCancellation($inquiry, true, ['refunded' => false]);
            $this->fail('Expected the release failure to bubble up.');
        } catch (\RuntimeException $e) {
            $this->assertSame('release failed', $e->getMessage());
        }

        // Everything rolled back: status, blocks, and stays unchanged.
        $this->assertSame(Inquiry::STATUS_CONFIRMED, $inquiry->refresh()->status);
        $this->assertSame(2, $this->blockCount($inquiry));
        $this->assertSame(2, Guest::find($inquiry->guest_id)->total_stays);
        $this->assertNull($inquiry->refresh()->refunded_at);
    }

    public function test_double_cancel_releases_once_and_decrements_once(): void
    {
        $inquiry = $this->confirmedBookingWithBlocks('doublecancel@example.com', guestStays: 2);
        $service = app(BookingCancellationService::class);

        $service->finalizeCancellation($inquiry, true, ['refunded' => false]);
        $service->finalizeCancellation($inquiry->refresh(), true, ['refunded' => false]);

        $this->assertSame(Inquiry::STATUS_CANCELLED, $inquiry->refresh()->status);
        $this->assertSame(0, $this->blockCount($inquiry));
        // Exactly one stay reversal across both calls — never below the
        // single recorded stay.
        $this->assertSame(1, Guest::find($inquiry->guest_id)->total_stays);
    }

    public function test_type_switch_cancel_releases_original_overnight_blocks(): void
    {
        $cottage = Cottage::first();

        $inquiry = $this->confirmedBookingWithBlocks('typeswitch@example.com', '2026-09-01', '2026-09-03', 'overnight');
        $this->assertSame(2, $this->blockCount($inquiry));

        // Back-to-back booking owns the checkout day (exclusive
        // [check_in, check_out) boundary from b37a7e4): it must survive.
        $next = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Next Guest',
            'email' => 'next@example.com',
            'phone' => '09170000001',
            'booking_type' => 'overnight',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-03',
            'check_out' => '2026-09-05',
            'pax' => 2,
            'status' => Inquiry::STATUS_CONFIRMED,
            'source' => 'website',
            'total_amount' => '5000.00',
        ]);
        $next->bookBlocks();
        $this->assertSame(2, CottageDateBlock::where('cottage_id', $cottage->id)->where('inquiry_id', $next->id)->count());

        // The caller holds switched (day_tour) attributes in memory, but the
        // persisted stay is overnight: the release must follow the locked
        // DB snapshot and clear both overnight nights.
        $inquiry->booking_type = Inquiry::TYPE_DAY_TOUR;

        app(BookingCancellationService::class)->finalizeCancellation($inquiry, true, ['refunded' => false]);

        $this->assertSame(Inquiry::STATUS_CANCELLED, $inquiry->refresh()->status);
        $this->assertSame(0, $this->blockCount($inquiry));
        $this->assertSame(
            ['2026-09-03', '2026-09-04'],
            CottageDateBlock::where('cottage_id', $cottage->id)
                ->where('inquiry_id', $next->id)
                ->orderBy('date')
                ->pluck('date')
                ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
                ->all()
        );
    }
}
