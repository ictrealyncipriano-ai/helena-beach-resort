<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\InquiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InquiryServiceConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_duplicate_dates_throw_validation_exception_not_500(): void
    {
        $cottage = Cottage::first();
        $service = app(InquiryService::class);

        // First booking holds the dates.
        $service->store([
            'name' => 'First',
            'email' => 'first@example.com',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'booking_type' => 'overnight',
            'pax' => 2,
        ]);

        // Second submission for an overlapping range (bypassing the request
        // validation layer entirely) must fail cleanly, not with a 500.
        try {
            $service->store([
                'name' => 'Second',
                'email' => 'second@example.com',
                'cottage_id' => $cottage->id,
                'check_in' => '2026-09-02',
                'check_out' => '2026-09-04',
                'booking_type' => 'overnight',
                'pax' => 2,
            ]);
            $this->fail('Expected a ValidationException for overlapping dates.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('check_in', $e->errors());
        }

        // No orphaned inquiry row from the failed submission.
        $this->assertDatabaseMissing('inquiries', ['email' => 'second@example.com']);

        // The first inquiry still holds its blocks.
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-02',
        ]);
    }

    public function test_reference_codes_are_unique_across_a_loop_of_creates(): void
    {
        $service = app(InquiryService::class);
        $codes = [];

        for ($i = 0; $i < 20; $i++) {
            $inquiry = $service->store([
                'name' => "Loop {$i}",
                'email' => "loop{$i}@example.com",
                'message' => 'msg',
            ]);
            $codes[] = $inquiry->reference_code;
        }

        $this->assertCount(20, $codes);
        $this->assertSame(20, count(array_unique($codes)));
    }

    public function test_admin_confirm_does_not_overwrite_foreign_block(): void
    {
        $cottage = Cottage::first();

        // Inquiry A legitimately holds the dates.
        $a = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'A',
            'email' => 'a@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'status' => 'pending',
            'source' => 'website',
        ]);
        $a->reserveBlocks();

        // Inquiry B is pending on the same range (a race/legacy state that
        // slipped past reservation), then an admin tries to confirm B.
        $b = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'B',
            'email' => 'b@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'status' => 'pending',
            'source' => 'website',
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.confirm', $b))
            ->assertRedirect()
            ->assertSessionHas('error');

        // B stays pending and A's block is not overwritten.
        $this->assertSame('pending', $b->refresh()->status);
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-02',
            'reason' => "Pending: {$a->reference_code}",
        ]);
    }

    public function test_conflicting_insert_does_not_overwrite_existing_block(): void
    {
        $cottage = Cottage::first();

        // Inquiry A legitimately holds the dates.
        $a = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'A',
            'email' => 'a-conflict@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'status' => 'pending',
            'source' => 'website',
        ]);
        $a->reserveBlocks();

        $b = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'B',
            'email' => 'b-conflict@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'status' => 'pending',
            'source' => 'website',
        ]);

        // Simulate the concurrent race that slips past the pre-flight SELECT:
        // B's write reaches the (cottage_id, date) unique index directly. The
        // ON CONFLICT DO NOTHING write (reserveBlocks/bookBlocks) must skip
        // the row rather than overwrite A's inquiry_id/reason (the old upsert
        // would have clobbered both).
        $now = now();
        $inserted = CottageDateBlock::insertOrIgnore([
            'cottage_id' => $cottage->id,
            'date' => '2026-09-02',
            'reason' => "Pending: {$b->reference_code}",
            'inquiry_id' => $b->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->assertSame(0, $inserted);

        // A's hold is intact — same inquiry_id and reason as originally set.
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-02',
            'inquiry_id' => $a->id,
            'reason' => "Pending: {$a->reference_code}",
        ]);

        // B never acquired the block via the FK or the legacy reason string.
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-02',
            'inquiry_id' => $b->id,
        ]);
    }

    public function test_book_blocks_does_not_conflict_with_own_pending_blocks(): void
    {
        $cottage = Cottage::first();

        // A pending inquiry already holds its own dates; confirming it
        // (bookBlocks) must treat those self-owned blocks as a promotion, not
        // as a conflict, even though the batched INSERT inserts zero rows.
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Promote',
            'email' => 'promote@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'status' => 'pending',
            'source' => 'website',
        ]);
        $inquiry->reserveBlocks();

        $inquiry->bookBlocks(); // must not throw

        $this->assertSame(3, CottageDateBlock::where('cottage_id', $cottage->id)
            ->where('inquiry_id', $inquiry->id)
            ->count());
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-02',
            'inquiry_id' => $inquiry->id,
            'reason' => "Booked: {$inquiry->reference_code}",
        ]);
    }
}
