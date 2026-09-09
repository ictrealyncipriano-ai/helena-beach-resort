<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WP-5: admin Resync Payment invokes the shared reconciliation service —
 * no duplicate payment logic — with manager-only access and throttling.
 */
class ResyncPaymentTest extends TestCase
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

    public function test_admin_resync_recovers_missing_payment(): void
    {
        $inquiry = $this->confirmedBooking('resync@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_resync_1',
        ]);
        $inquiry->refresh();

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => [
                    'id' => 'cs_resync_1',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'reference_number' => $inquiry->reference_code,
                        'paid_at' => 1785892089,
                        'payments' => [
                            [
                                'id' => 'pay_resync_1',
                                'attributes' => [
                                    'status' => 'paid',
                                    'amount' => (int) round((float) $inquiry->total_amount * 100),
                                    'currency' => 'PHP',
                                    'source' => ['type' => 'qrph'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.resync-payment', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertTrue($inquiry->isPaid());
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'payment.resynced',
            'subject_id' => $inquiry->id,
        ]);
    }

    public function test_admin_resync_with_nothing_remote_leaves_state_unchanged(): void
    {
        $inquiry = $this->confirmedBooking('resyncnone@example.com');

        Http::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.resync-payment', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('info');

        Http::assertNothingSent();
        $this->assertFalse($inquiry->refresh()->isPaid());
    }

    public function test_staff_cannot_resync(): void
    {
        $inquiry = $this->confirmedBooking('resyncstaff@example.com');
        $staff = User::factory()->create(['role' => 'staff']);

        Http::fake();

        $this->actingAs($staff)
            ->post(route('admin.inquiries.resync-payment', $inquiry))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_guest_cannot_resync(): void
    {
        $inquiry = $this->confirmedBooking('resyncguest@example.com');
        auth()->logout();

        $this->post(route('admin.inquiries.resync-payment', $inquiry))
            ->assertRedirect(route('admin.login'));
    }
}
