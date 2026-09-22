<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Slice 11 — integrity hardening (characterization-first).
 *
 * Desired end state (must FAIL pre-fix unless noted as guard):
 *  - Manual-payment idempotency keys are found by an indexed JSON-path
 *    lookup no matter how deep the ledger grows (no 25-row scan).
 *  - Cottage deletion is blocked whenever linked inquiries or
 *    testimonials exist — not only for active booking blocks.
 *  - Cottage money/capacity attributes hydrate with exact casts.
 *  - The admin cottage index only hydrates the recent date-block window.
 */
class IntegrityBoundaryCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function cottage(array $overrides = []): Cottage
    {
        return Cottage::create(array_merge([
            'name' => 'Integrity Villa',
            'capacity' => 4,
            'rate_daytour' => 1500,
            'rate_overnight' => 3000,
            'is_available' => true,
        ], $overrides));
    }

    public function test_idempotency_key_found_past_twenty_five_rows(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Idempotent Guest',
            'email' => 'idem@example.com',
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'source' => 'website',
            'cottage_id' => $this->cottage()->id,
            'total_amount' => '100000.00',
        ]);

        for ($i = 1; $i <= 30; $i++) {
            $inquiry->recordManualPayment('100.00', Inquiry::METHOD_MANUAL, "key-{$i}");
        }

        $this->assertSame(30, Payment::where('inquiry_id', $inquiry->id)->count());

        // Replaying the OLDEST key must reuse the stored outcome, not write.
        $inquiry->recordManualPayment('100.00', Inquiry::METHOD_MANUAL, 'key-1');

        $this->assertSame(30, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_cottage_with_only_cancelled_history_cannot_delete(): void
    {
        $cottage = $this->cottage(['name' => 'History Villa']);
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Old Guest',
            'email' => 'oldhistory@example.com',
            'booking_type' => 'day_tour',
            'status' => 'cancelled',
            'source' => 'website',
            'cottage_id' => $cottage->id,
            'total_amount' => '1500.00',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.cottages.destroy', $cottage))
            ->assertSessionHas('error');

        $this->assertNotNull(Cottage::find($cottage->id));
        $this->assertSame($cottage->id, $inquiry->refresh()->cottage_id);
    }

    public function test_cottage_with_testimonial_cannot_delete(): void
    {
        $cottage = $this->cottage(['name' => 'Reviewed Villa']);
        Testimonial::create([
            'guest_name' => 'Reviewer',
            'content' => 'Lovely stay by the sea.',
            'rating' => 5,
            'cottage_id' => $cottage->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.cottages.destroy', $cottage))
            ->assertSessionHas('error');

        $this->assertNotNull(Cottage::find($cottage->id));
    }

    public function test_cottage_money_and_capacity_casts_are_exact(): void
    {
        $cottage = $this->cottage([
            'capacity' => 6,
            'rate_daytour' => 2000,
            'rate_overnight' => 3500,
            'sort_order' => 3,
        ])->fresh();

        $this->assertSame('2000.00', $cottage->rate_daytour);
        $this->assertSame('3500.00', $cottage->rate_overnight);
        $this->assertSame(6, $cottage->capacity);
        $this->assertSame(3, $cottage->sort_order);
    }

    public function test_admin_index_hydrates_only_recent_date_blocks(): void
    {
        $cottage = $this->cottage(['name' => 'Blocked Villa']);
        $ancient = now()->subMonths(6)->toDateString();
        $upcoming = now()->addDays(10)->toDateString();

        $cottage->dateBlocks()->create(['date' => $ancient, 'reason' => 'Old']);
        $cottage->dateBlocks()->create(['date' => $upcoming, 'reason' => 'New']);

        $body = $this->actingAs($this->admin())
            ->get(route('admin.cottages.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($upcoming, $body);
        $this->assertStringNotContainsString($ancient, $body);
    }
}
