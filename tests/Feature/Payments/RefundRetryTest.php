<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WP-7: failed online refunds persist as failed + attempts + last_error,
 * the admin sees a requires-attention banner with Retry, and
 * payments:retry-refunds recovers them with backoff. Manual money never
 * enters this lifecycle.
 */
class RefundRetryTest extends TestCase
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

    private function paidOnline(Inquiry $inquiry, string $paymentId = 'pay_retry_1'): Inquiry
    {
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => $paymentId,
        ]);
        Payment::recordPaid($inquiry->refresh(), (string) $inquiry->total_amount, 'qrph', $paymentId, 'cs_retry', Payment::TYPE_FULL);

        return $inquiry->refresh();
    }

    public function test_failed_admin_refund_persists_failed_state(): void
    {
        $inquiry = $this->paidOnline($this->confirmedBooking('retryfail@example.com'));

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['errors' => ['boom']], 500),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.refund', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('error');

        $inquiry->refresh();
        $this->assertNull($inquiry->refunded_at);
        $this->assertSame(RefundService::STATUS_FAILED, $inquiry->refund_status);
        $this->assertSame(1, $inquiry->refund_attempts);
        $this->assertNotNull($inquiry->refund_last_error);
        // No false success anywhere.
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->where('type', Payment::TYPE_REFUND)->count());
    }

    public function test_successful_refund_marks_completed(): void
    {
        $inquiry = $this->paidOnline($this->confirmedBooking('retryok@example.com'));

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_ok']], 200),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.refund', $inquiry));

        $inquiry->refresh();
        $this->assertSame(RefundService::STATUS_COMPLETED, $inquiry->refund_status);
        $this->assertNotNull($inquiry->refunded_at);
    }

    public function test_retry_command_recovers_failed_refund(): void
    {
        $inquiry = $this->paidOnline($this->confirmedBooking('retrycmd@example.com'));

        // First attempt (admin click) fails, retry succeeds: one sequence.
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::sequence()
                ->pushStatus(500, ['errors' => ['boom']])
                ->push(['data' => ['id' => 'rfnd_retry_1']], 200),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.refund', $inquiry));
        $this->assertSame(RefundService::STATUS_FAILED, $inquiry->refresh()->refund_status);

        // Past the 15-minute backoff for attempt 1.
        Inquiry::where('id', $inquiry->id)->update(['updated_at' => now()->subHour()]);

        $this->artisan('payments:retry-refunds')->assertSuccessful();

        $inquiry->refresh();
        $this->assertSame(RefundService::STATUS_COMPLETED, $inquiry->refund_status);
        $this->assertNotNull($inquiry->refunded_at);
        $this->assertNotNull(
            Payment::where('inquiry_id', $inquiry->id)->where('type', Payment::TYPE_REFUND)->first()
        );
    }

    public function test_retry_command_respects_backoff_and_max_attempts(): void
    {
        $inquiry = $this->paidOnline($this->confirmedBooking('retryback@example.com'));
        $inquiry->update([
            'refund_status' => RefundService::STATUS_FAILED,
            'refund_attempts' => RefundService::MAX_ATTEMPTS,
            'refund_last_error' => 'boom',
        ]);

        Http::fake();

        $this->artisan('payments:retry-refunds')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(RefundService::STATUS_FAILED, $inquiry->refresh()->refund_status);
    }

    public function test_retry_command_recovers_stuck_late_payment_ledger_row(): void
    {
        $inquiry = $this->confirmedBooking('retrylate@example.com');
        $inquiry->update(['status' => Inquiry::STATUS_CANCELLED]);

        // Late webhook refund fails first, command retry succeeds.
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::sequence()
                ->pushStatus(500, ['errors' => ['boom']])
                ->push(['data' => ['id' => 'rfnd_retry_late']], 200),
        ]);

        $payload = json_encode([
            'data' => [
                'id' => 'cs_retry_late',
                'type' => 'checkout_session',
                'attributes' => [
                    'reference_number' => $inquiry->reference_code,
                    'payments' => [
                        [
                            'id' => 'pay_retry_late',
                            'attributes' => ['status' => 'paid', 'amount' => (int) round((float) $inquiry->total_amount * 100), 'currency' => 'PHP', 'source' => ['type' => 'qrph']],
                        ],
                    ],
                ],
            ],
        ]);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'test-webhook-secret');

        $this->postJson(route('payment.webhook'), json_decode($payload, true), [
            'Paymongo-Signature' => "t={$timestamp},te={$signature},li=",
        ])->assertOk()->assertJson(['refund' => 'failed']);

        $this->assertSame(
            Payment::STATUS_REQUIRES_REFUND,
            Payment::where('provider_payment_id', 'pay_retry_late')->firstOrFail()->status
        );

        // Past backoff, provider healthy now.
        Payment::where('provider_payment_id', 'pay_retry_late')->update(['updated_at' => now()->subHour()]);

        $this->artisan('payments:retry-refunds')->assertSuccessful();

        $this->assertSame(
            Payment::STATUS_REFUNDED,
            Payment::where('provider_payment_id', 'pay_retry_late')->firstOrFail()->status
        );
    }

    public function test_manual_money_never_enters_refund_lifecycle(): void
    {
        $inquiry = $this->confirmedBooking('retrymanual@example.com');
        $inquiry->recordManualPayment((string) $inquiry->total_amount);

        Http::fake();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)
            ->post(route('admin.inquiries.refund', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $inquiry->refresh();
        $this->assertSame(RefundService::STATUS_NONE, $inquiry->refund_status);
        $this->assertSame(0, $inquiry->refund_attempts);
    }
}
