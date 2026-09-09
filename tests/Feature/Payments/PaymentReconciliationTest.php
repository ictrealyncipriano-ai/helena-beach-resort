<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WP-2: centralized reconciliation by strongest identifier.
 * Remote PayMongo reads are faked; local writes must match the webhook's
 * locked semantics (idempotent, amount-verified, dual-written).
 */
class PaymentReconciliationTest extends TestCase
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

    private function checkoutSessionResponse(Inquiry $inquiry, string $sessionId, string $paymentId, ?int $centavos, bool $paid = true): array
    {
        return [
            'id' => $sessionId,
            'type' => 'checkout_session',
            'attributes' => [
                'reference_number' => $inquiry->reference_code,
                'currency' => 'PHP',
                'paid_at' => $paid ? 1785892089 : null,
                'payments' => [
                    [
                        'id' => $paymentId,
                        'attributes' => [
                            'status' => $paid ? 'paid' : 'awaiting_payment',
                            'amount' => $centavos,
                            'currency' => 'PHP',
                            'source' => ['type' => 'qrph'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_reconcile_by_checkout_id_records_missing_payment(): void
    {
        // Webhook never arrived, but PayMongo says paid: reconcile recovers it.
        $inquiry = $this->confirmedBooking('recon@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_recon_1',
        ]);
        $inquiry->refresh();

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->checkoutSessionResponse(
                    $inquiry, 'cs_recon_1', 'pay_recon_1',
                    (int) round((float) $inquiry->total_amount * 100)
                ),
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_recon_1');

        $this->assertSame('recorded', $result['outcome']);
        $this->assertSame($inquiry->id, $result['inquiry_id']);

        $inquiry->refresh();
        $this->assertTrue($inquiry->isPaid());
        $this->assertNull($inquiry->payment_pending_amount);
        $this->assertSame('pay_recon_1', $inquiry->paymongo_payment_id);
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_reconcile_by_checkout_id_is_idempotent(): void
    {
        $inquiry = $this->confirmedBooking('reconidem@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_recon_idem',
        ]);
        $inquiry->refresh();

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->checkoutSessionResponse(
                    $inquiry, 'cs_recon_idem', 'pay_recon_idem',
                    (int) round((float) $inquiry->total_amount * 100)
                ),
            ], 200),
        ]);

        $service = app(PaymentReconciliationService::class);
        $this->assertSame('recorded', $service->reconcileByCheckoutId('cs_recon_idem')['outcome']);
        $this->assertSame('already_paid', $service->reconcileByCheckoutId('cs_recon_idem')['outcome']);
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_reconcile_by_checkout_id_reports_not_paid_without_mutating(): void
    {
        $inquiry = $this->confirmedBooking('reconnotpaid@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_recon_np',
        ]);

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->checkoutSessionResponse(
                    $inquiry, 'cs_recon_np', 'pay_recon_np',
                    (int) round((float) $inquiry->total_amount * 100), false
                ),
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_recon_np');

        $this->assertSame('not_paid', $result['outcome']);
        $this->assertNull($inquiry->refresh()->fully_paid_at);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_reconcile_by_checkout_id_reports_mismatch_without_writing(): void
    {
        $inquiry = $this->confirmedBooking('reconmm@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_recon_mm',
        ]);

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->checkoutSessionResponse($inquiry, 'cs_recon_mm', 'pay_recon_mm', 1),
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_recon_mm');

        $this->assertSame('mismatch', $result['outcome']);
        $this->assertNull($inquiry->refresh()->fully_paid_at);
        $this->assertNull($inquiry->refresh()->paymongo_payment_id);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_reconcile_by_checkout_id_with_unknown_reference_is_unmatched(): void
    {
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => [
                    'id' => 'cs_unknown',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'reference_number' => 'HB-NOSUCHCODE',
                        'paid_at' => 1785892089,
                        'payments' => [
                            ['id' => 'pay_x', 'attributes' => ['status' => 'paid', 'amount' => 10000, 'source' => ['type' => 'qrph']]],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_unknown');

        $this->assertSame('unmatched', $result['outcome']);
    }

    public function test_reconcile_by_inquiry_with_nothing_remote_reports_nothing_to_reconcile(): void
    {
        $inquiry = $this->confirmedBooking('reconnothing@example.com');

        Http::fake();

        $result = app(PaymentReconciliationService::class)->reconcileByInquiry($inquiry);

        $this->assertSame('nothing_to_reconcile', $result['outcome']);
        Http::assertNothingSent();
    }

    public function test_reconcile_by_inquiry_delegates_to_checkout(): void
    {
        $inquiry = $this->confirmedBooking('recondel@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_recon_del',
        ]);
        $inquiry->refresh();

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->checkoutSessionResponse(
                    $inquiry, 'cs_recon_del', 'pay_recon_del',
                    (int) round((float) $inquiry->total_amount * 100)
                ),
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByInquiry($inquiry);

        $this->assertSame('recorded', $result['outcome']);
        $this->assertTrue($inquiry->refresh()->isPaid());
    }

    public function test_reconcile_by_payment_id_matches_existing_ledger_row(): void
    {
        $inquiry = $this->confirmedBooking('reconpay@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_recon_match',
        ]);
        Payment::recordPaid($inquiry->refresh(), (string) $inquiry->total_amount, 'qrph', 'pay_recon_match', 'cs_m', Payment::TYPE_FULL);

        Http::fake([
            'api.paymongo.com/v1/payments/*' => Http::response([
                'data' => [
                    'id' => 'pay_recon_match',
                    'type' => 'payment',
                    'attributes' => [
                        'status' => 'paid',
                        'amount' => (int) round((float) $inquiry->total_amount * 100),
                        'currency' => 'PHP',
                    ],
                ],
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByPaymentId('pay_recon_match');

        $this->assertSame('matched', $result['outcome']);
        $this->assertSame($inquiry->id, $result['inquiry_id']);
    }

    public function test_reconcile_by_payment_id_without_local_row_is_unmatched_and_never_writes(): void
    {
        Http::fake([
            'api.paymongo.com/v1/payments/*' => Http::response([
                'data' => [
                    'id' => 'pay_orphan',
                    'type' => 'payment',
                    'attributes' => ['status' => 'paid', 'amount' => 99900, 'currency' => 'PHP'],
                ],
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByPaymentId('pay_orphan');

        $this->assertSame('unmatched', $result['outcome']);
        $this->assertSame(0, Payment::where('provider_payment_id', 'pay_orphan')->count());
    }
}
