<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiredReservationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createPendingInquiry(string $email, string $createdAt): Inquiry
    {
        $cottage = Cottage::first();

        $inquiry = Inquiry::create([
            'name' => 'Guest',
            'email' => $email,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        $inquiry->created_at = $createdAt;
        $inquiry->save();

        $inquiry->reserveBlocks();

        return $inquiry;
    }

    public function test_command_expires_stale_pending_and_releases_blocks(): void
    {
        $inquiry = $this->createPendingInquiry('stale@example.com', now()->subHours(72)->toDateTimeString());

        $this->artisan('reservations:release-expired --hours=48')
            ->expectsOutputToContain($inquiry->reference_code)
            ->assertExitCode(0);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
        ]);
    }

    public function test_command_leaves_recent_pending_untouched(): void
    {
        $inquiry = $this->createPendingInquiry('fresh@example.com', now()->subHours(2)->toDateTimeString());

        $this->artisan('reservations:release-expired --hours=48')
            ->assertExitCode(0);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
        ]);
    }

    /**
     * The warning window is anchored on time-remaining-to-expiry (hold ends
     * within the next 12h), so a booking created at any age inside that band
     * is warned regardless of when the cron fires.
     */
    public function test_command_warns_inquiries_expiring_soon(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        // Created 40h ago with a 48h hold: expires in ~8h -> should be warned,
        // not expired.
        $inquiry = $this->createPendingInquiry('warned@example.com', now()->subHours(40)->toDateTimeString());

        $this->artisan('reservations:release-expired --hours=48')
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\BookingExpiringSoon::class, fn ($mail) => $mail->hasTo($inquiry->email));

        $this->assertNotNull($inquiry->fresh()->expiry_warned_at);
        $this->assertSame('pending', $inquiry->fresh()->status);
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
        ]);
    }

    /**
     * A booking that is already past its hold window (expired by age) is not
     * warned first — it is expired and its blocks released.
     */
    public function test_command_does_not_warn_already_expired_inquiries(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $inquiry = $this->createPendingInquiry('expired-now@example.com', now()->subHours(49)->toDateTimeString());

        $this->artisan('reservations:release-expired --hours=48')
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Mail::assertNotSent(\App\Mail\BookingExpiringSoon::class);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'status' => 'expired',
        ]);
    }

    /**
     * The command processes rows with chunkById(100) and one bulk UPDATE /
     * one bulk DELETE per chunk, so volumes larger than a single chunk must
     * still expire every stale inquiry and release every held block.
     */
    public function test_command_batches_across_chunks_and_releases_all_blocks(): void
    {
        $cottage = Cottage::first();

        // More stale inquiries than one chunkById(100) batch can hold.
        // Check-ins are staggered 5 days apart so reserveBlocks() never
        // trips a BookingConflictException on an overlapping date.
        $inquiries = collect(range(1, 105))->map(function ($i) use ($cottage) {
            $inquiry = Inquiry::create([
                'name' => 'Stale Guest '.$i,
                'email' => "stale-$i@example.com",
                'check_in' => now()->addDays($i * 5)->toDateString(),
                'check_out' => now()->addDays($i * 5 + 1)->toDateString(),
                'cottage_id' => $cottage->id,
                'source' => 'website',
            ]);
            $inquiry->created_at = now()->subHours(72)->toDateTimeString();
            $inquiry->save();
            $inquiry->reserveBlocks();

            return $inquiry;
        });

        $this->artisan('reservations:release-expired --hours=48')
            ->assertExitCode(0);

        $this->assertSame(105, Inquiry::where('status', 'expired')->count());
        $this->assertSame(0, CottageDateBlock::where('cottage_id', $cottage->id)->count());

        foreach ($inquiries as $inquiry) {
            $this->assertDatabaseHas('inquiries', [
                'id' => $inquiry->id,
                'status' => 'expired',
            ]);
        }
    }
}
