<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WP-4: payments:reconcile is conservative — dry-run never touches the API
 * or the DB, live runs clear/record via the shared service, and
 * non-confirmed bookings with sessions are flagged, never mutated.
 */
class ReconcileCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function confirmedBooking(string $email): Inquiry
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', $email)->firstOrFail();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    public function test_dry_run_reports_without_api_calls_or_writes(): void
    {
        $inquiry = $this->confirmedBooking('recdry@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHours(5),
            'paymongo_session_id' => 'cs_rec_dry',
        ]);

        Http::fake();

        $this->artisan('payments:reconcile', ['--dry-run' => true])->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNotNull($inquiry->refresh()->payment_pending_amount);
    }

    public function test_reconcile_clears_stale_unpaid_checkout(): void
    {
        $inquiry = $this->confirmedBooking('recrun@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHours(5),
            'paymongo_session_id' => 'cs_rec_run',
        ]);
        $inquiry->refresh();

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => [
                    'id' => 'cs_rec_run',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'reference_number' => $inquiry->reference_code,
                        'paid_at' => null,
                        'payments' => [],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertNull($inquiry->refresh()->payment_pending_amount);
    }

    public function test_reconcile_flags_cancelled_booking_with_session_without_mutating_money(): void
    {
        $inquiry = $this->confirmedBooking('recflag@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHours(5),
            'paymongo_session_id' => 'cs_rec_flag',
        ]);
        $inquiry->refresh();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry));
        $this->assertSame(Inquiry::STATUS_CANCELLED, $inquiry->refresh()->status);

        // Cancel releases blocks but the session pointer stays: the command
        // must flag it, then the sweep clears the leftover pending — but no
        // money may ever be recorded against the cancelled booking.
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHours(5),
            'paymongo_session_id' => 'cs_rec_flag',
        ]);

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => [
                    'id' => 'cs_rec_flag',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'reference_number' => $inquiry->refresh()->reference_code,
                        'paid_at' => null,
                        'payments' => [],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $inquiry->refresh();
        $this->assertSame('0.00', (string) ($inquiry->amount_paid ?? '0.00'));
        $this->assertNull($inquiry->paymongo_payment_id);
    }
}
