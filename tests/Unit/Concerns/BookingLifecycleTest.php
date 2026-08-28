<?php

namespace Tests\Unit\Concerns;

use App\Concerns\CancelsBookings;
use App\Concerns\ConfirmsBookings;
use App\Http\Controllers\Admin\DashboardController;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Phase 8.9 — the confirmation/cancellation lifecycle primitives moved into
 * the CancelsBookings and ConfirmsBookings concerns. These tests drive the
 * trait methods directly via small anonymous harness classes.
 */
class BookingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
        Mail::fake();
    }

    /** Small harness exposing the protected trait method. */
    private function canceller(): object
    {
        return new class
        {
            use CancelsBookings;

            public function cancel(Inquiry $inquiry): void
            {
                $this->cancelBooking($inquiry);
            }

            public function reverseIfNeeded(Inquiry $inquiry): void
            {
                $this->reverseStayIfNeeded($inquiry);
            }

            public function email(Inquiry $inquiry): void
            {
                $this->sendCancellationEmails($inquiry);
            }
        };
    }

    private function confirmer(): object
    {
        return new class
        {
            use ConfirmsBookings;

            public function mark(Inquiry $inquiry): void
            {
                $this->markConfirmed($inquiry);
            }
        };
    }

    private function cottage(): Cottage
    {
        return Cottage::first();
    }

    private function pendingBooking(string $reference): Inquiry
    {
        return Inquiry::create([
            'reference_code' => $reference,
            'name' => 'Guest',
            'email' => strtolower($reference).'@example.com',
            'source' => Inquiry::SOURCE_WEBSITE,
            'status' => Inquiry::STATUS_PENDING,
            'cottage_id' => $this->cottage()->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-12',
        ]);
    }

    public function test_cancel_booking_sets_status_releases_blocks_and_forgets_cache(): void
    {
        $inquiry = $this->pendingBooking('HB-CANCEL');
        $inquiry->reserveBlocks();
        Cache::put(DashboardController::cacheKey(), ['x' => 1], 300);

        $this->canceller()->cancel($inquiry);

        $this->assertSame(Inquiry::STATUS_CANCELLED, $inquiry->fresh()->status);
        $this->assertCount(0, CottageDateBlock::where('inquiry_id', $inquiry->id)->get());
        $this->assertFalse(Cache::has(DashboardController::cacheKey()));
    }

    public function test_reverse_stay_only_when_confirmed(): void
    {
        $guest = Guest::create(['name' => 'Guest', 'email' => 'reverse@example.com', 'total_stays' => 1]);
        $pending = $this->pendingBooking('HB-NOREV');
        $pending->guest()->associate($guest)->save();

        // Pending: count untouched.
        $this->canceller()->reverseIfNeeded($pending);
        $this->assertSame(1, $guest->fresh()->total_stays);

        // Confirmed: count reversed.
        $pending->update(['status' => Inquiry::STATUS_CONFIRMED]);
        $this->canceller()->reverseIfNeeded($pending->fresh());
        $this->assertSame(0, $guest->fresh()->total_stays);
    }

    public function test_send_cancellation_emails_to_guest_and_owner(): void
    {
        SiteSetting::updateOrCreate(
            ['key' => 'contact_email'],
            ['value' => 'owner@example.com', 'type' => 'text'],
        );
        SiteSetting::forgetCache();
        $inquiry = $this->pendingBooking('HB-EML');

        $this->canceller()->email($inquiry);

        Mail::assertSent(BookingCancelled::class, fn ($mail) => $mail->hasTo($inquiry->email));
        Mail::assertSent(BookingCancelled::class, fn ($mail) => $mail->hasTo('owner@example.com'));
    }

    public function test_send_cancellation_emails_tolerates_no_owner_email(): void
    {
        Mail::fake();
        $inquiry = $this->pendingBooking('HB-EML2');

        // Must not throw when the owner email is absent.
        $this->canceller()->email($inquiry);

        Mail::assertSent(BookingCancelled::class, fn ($mail) => $mail->hasTo($inquiry->email));
    }

    public function test_mark_confirmed_increments_stay_and_sends_email(): void
    {
        $guest = Guest::create(['name' => 'Guest', 'email' => 'confirm@example.com', 'total_stays' => 2]);
        $inquiry = $this->pendingBooking('HB-CONFIRM');
        $inquiry->guest()->associate($guest)->save();
        $inquiry->update(['status' => Inquiry::STATUS_CONFIRMED]);

        $this->confirmer()->mark($inquiry->fresh());

        $this->assertSame(3, $guest->fresh()->total_stays);
        $this->assertNotNull($guest->fresh()->last_stay_at);
        Mail::assertSent(BookingConfirmed::class, fn ($mail) => $mail->hasTo($inquiry->email));
    }

    public function test_mark_confirmed_skips_stay_when_no_guest(): void
    {
        $inquiry = $this->pendingBooking('HB-NOGUEST');

        // Must not throw when there is no linked guest.
        $this->confirmer()->mark($inquiry);

        Mail::assertSent(BookingConfirmed::class, fn ($mail) => $mail->hasTo($inquiry->email));
    }
}
