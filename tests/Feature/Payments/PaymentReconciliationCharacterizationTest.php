<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentReconciliationService;
use App\Services\PayMongoService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 6 characterization: PaymentReconciliationService observable baseline.
 *
 * TEST-ONLY slice: no production code changes. Pins what callers can
 * observe — centavo acceptance, payment rows (amount/status/type),
 * inquiry fields, and outcome strings — not implementation details.
 *
 * Separate converter-oracle tests document PayMongoService::toCentavos()
 * (float round) vs Money::toCentavos() (string half-up) divergence for
 * Phase 7. Legacy sweep tolerance (abs < 0.005) is preserved as
 * characterization. InvoiceController, PromoCode, and admin arithmetic
 * are out of scope.
 */
class PaymentReconciliationCharacterizationTest extends TestCase
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

    private function splitBooking(string $email): Inquiry
    {
        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Guest',
            'email' => $email,
            'phone' => '09170000000',
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
            'status' => Inquiry::STATUS_CONFIRMED,
            'source' => 'website',
            'total_amount' => '5000.00',
            'deposit_amount' => '1000.00',
            'amount_paid' => '0.00',
        ]);
    }

    private function checkoutSessionResponse(
        Inquiry $inquiry,
        string $sessionId,
        string $paymentId,
        ?int $centavos,
        bool $paid = true,
        string $currency = 'PHP',
    ): array {
        return [
            'id' => $sessionId,
            'type' => 'checkout_session',
            'attributes' => [
                'reference_number' => $inquiry->reference_code,
                'currency' => $currency,
                'paid_at' => $paid ? 1785892089 : null,
                'payments' => [
                    [
                        'id' => $paymentId,
                        'attributes' => [
                            'status' => $paid ? 'paid' : 'awaiting_payment',
                            'amount' => $centavos,
                            'currency' => $currency,
                            'source' => ['type' => 'qrph'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function fakeCheckout(Inquiry $inquiry, string $sessionId, string $paymentId, ?int $centavos, bool $paid = true, string $currency = 'PHP'): void
    {
        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->checkoutSessionResponse($inquiry, $sessionId, $paymentId, $centavos, $paid, $currency),
            ], 200),
        ]);
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => ['token' => $inquiry->token, 'granted_at' => now()->toDateTimeString()]]];
    }

    // ---- Converter oracle (implementation-level divergence notes) ----

    public function test_converter_oracle_normal_inputs_agree(): void
    {
        $paymongo = app(PayMongoService::class);

        foreach (['1500.00' => 150000, '5000.00' => 500000, '1000.00' => 100000, '4000.00' => 400000, '0.00' => 0] as $input => $expected) {
            $this->assertSame($expected, $paymongo->toCentavos($input), "paymongo {$input}");
            $this->assertSame($expected, Money::toCentavos($input), "money {$input}");
        }

        $this->assertSame(0, $paymongo->toCentavos(null));
        $this->assertSame(0, $paymongo->toCentavos(''));
        $this->assertSame(0, Money::toCentavos(null));
    }

    public function test_converter_oracle_half_up_edge_documents_divergence(): void
    {
        // Money contract anchor (string half-up): 10.005 -> 10.01 -> 1001.
        $this->assertSame(1001, Money::toCentavos('10.005'));
        $this->assertSame(268, Money::toCentavos('2.675'));

        // PayMongo uses round((float), 2): binary float may round half-even
        // down on these edges. Document current value without forcing the
        // Phase 7 outcome.
        $paymongo = app(PayMongoService::class);
        $ten = $paymongo->toCentavos('10.005');
        $two = $paymongo->toCentavos('2.675');

        $this->assertIsInt($ten);
        $this->assertIsInt($two);
        $this->assertTrue(
            in_array($ten, [1000, 1001], true),
            "Phase 7 note: paymongo 10.005 currently {$ten} vs Money 1001"
        );
        $this->assertTrue(
            in_array($two, [267, 268], true),
            "Phase 7 note: paymongo 2.675 currently {$two} vs Money 268"
        );
    }

    // ---- Normal / deposit / full-payment branches ----

    public function test_full_payment_records_exact_delivered_amount(): void
    {
        // NOTE: the ledger row is classified as `balance`, not
        // `full_payment`: the service updates the locked inquiry BEFORE
        // calling Payment::classifyType($locked, ...), so the classifier sees
        // post-update amount_paid (> 0 whenever anything was credited) and
        // takes the balance branch. `full_payment` is unreachable via this
        // path (Phase 7 note).
        $inquiry = $this->confirmedBooking('charfull@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_char_full',
        ]);
        $inquiry->refresh();
        $centavos = (int) round((float) $inquiry->total_amount * 100);

        $this->fakeCheckout($inquiry, 'cs_char_full', 'pay_char_full', $centavos);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_full');

        $this->assertSame('recorded', $result['outcome']);
        $inquiry->refresh();
        $this->assertSame((string) $inquiry->total_amount, (string) $inquiry->amount_paid);
        $this->assertNull($inquiry->payment_pending_amount);
        $this->assertNotNull($inquiry->fully_paid_at);
        $this->assertNull($inquiry->deposit_paid_at);
        $this->assertSame('pay_char_full', $inquiry->paymongo_payment_id);

        $ledger = Payment::where('provider_payment_id', 'pay_char_full')->firstOrFail();
        $this->assertSame((string) $inquiry->total_amount, (string) $ledger->amount);
        $this->assertSame(Payment::STATUS_PAID, $ledger->status);
        $this->assertSame(Payment::TYPE_BALANCE, $ledger->type);
        $this->assertSame('PHP', $ledger->currency);
    }

    public function test_deposit_leg_records_deposit_type_and_leaves_balance(): void
    {
        $inquiry = $this->splitBooking('chardep@example.com');
        $inquiry->update([
            'payment_pending_amount' => '1000.00',
            'paymongo_session_id' => 'cs_char_dep',
        ]);

        $this->fakeCheckout($inquiry->refresh(), 'cs_char_dep', 'pay_char_dep', 100000);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_dep');

        $this->assertSame('recorded', $result['outcome']);
        $inquiry->refresh();
        $this->assertSame('1000.00', (string) $inquiry->amount_paid);
        $this->assertNotNull($inquiry->deposit_paid_at);
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertSame(
            '4000.00',
            $inquiry->outstandingBalance(),
            'deposit leaves the exact outstanding balance observable'
        );
        $this->assertSame(Payment::TYPE_DEPOSIT, Payment::where('provider_payment_id', 'pay_char_dep')->firstOrFail()->type);
    }

    public function test_balance_after_deposit_with_swept_pending_accepts_outstanding(): void
    {
        $inquiry = $this->splitBooking('charbal@example.com');
        $inquiry->update(['amount_paid' => '1000.00', 'deposit_paid_at' => now()]);
        Payment::recordPaid($inquiry->refresh(), '1000.00', 'qrph', 'pay_char_bal_dep', 'cs_char_bal_dep', Payment::TYPE_DEPOSIT);
        // Pending baseline swept (cleared); balance checkout must verify
        // against the outstanding balance, crediting exactly delivered.
        $inquiry->update(['payment_pending_amount' => null, 'paymongo_session_id' => null]);

        $this->fakeCheckout($inquiry->refresh(), 'cs_char_bal', 'pay_char_bal', 400000);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_bal');

        $this->assertSame('recorded', $result['outcome']);
        $inquiry->refresh();
        $this->assertSame('5000.00', (string) $inquiry->amount_paid);
        $this->assertNotNull($inquiry->fully_paid_at);
        $this->assertSame(2, Payment::where('inquiry_id', $inquiry->id)->count());
        $this->assertSame('4000.00', (string) Payment::where('provider_payment_id', 'pay_char_bal')->firstOrFail()->amount);
        $this->assertSame(Payment::TYPE_BALANCE, Payment::where('provider_payment_id', 'pay_char_bal')->firstOrFail()->type);
    }

    // ---- Exact equality: underpay / overpay / currency ----

    public function test_underpay_reports_mismatch_without_writing(): void
    {
        $inquiry = $this->confirmedBooking('charunder@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_char_under',
        ]);

        $this->fakeCheckout($inquiry->refresh(), 'cs_char_under', 'pay_char_under', 1);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_under');

        $this->assertSame('mismatch', $result['outcome']);
        $inquiry->refresh();
        $this->assertSame('0.00', (string) ($inquiry->amount_paid ?? '0.00'));
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertNull($inquiry->paymongo_payment_id);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_overpay_reports_mismatch_without_writing(): void
    {
        $inquiry = $this->confirmedBooking('charover@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_char_over',
        ]);
        $inquiry->refresh();
        $over = (int) round((float) $inquiry->total_amount * 100) + 5000;

        $this->fakeCheckout($inquiry, 'cs_char_over', 'pay_char_over', $over);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_over');

        $this->assertSame('mismatch', $result['outcome'], 'acceptance is exact: over-delivery is not credited');
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
        $this->assertSame('0.00', (string) ($inquiry->refresh()->amount_paid ?? '0.00'));
    }

    public function test_non_php_currency_reports_mismatch_without_writing(): void
    {
        $inquiry = $this->confirmedBooking('charcur@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_char_cur',
        ]);
        $inquiry->refresh();
        $centavos = (int) round((float) $inquiry->total_amount * 100);

        $this->fakeCheckout($inquiry, 'cs_char_cur', 'pay_char_cur', $centavos, true, 'USD');

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_cur');

        $this->assertSame('mismatch', $result['outcome']);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    // ---- Idempotency ----

    public function test_duplicate_full_delivery_maps_already_paid(): void
    {
        $inquiry = $this->confirmedBooking('charidem@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_char_idem',
        ]);
        $inquiry->refresh();
        $centavos = (int) round((float) $inquiry->total_amount * 100);

        $this->fakeCheckout($inquiry, 'cs_char_idem', 'pay_char_idem', $centavos);

        $service = app(PaymentReconciliationService::class);
        $this->assertSame('recorded', $service->reconcileByCheckoutId('cs_char_idem')['outcome']);
        $this->assertSame('already_paid', $service->reconcileByCheckoutId('cs_char_idem')['outcome']);
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_duplicate_deposit_delivery_maps_duplicate_payment(): void
    {
        $inquiry = $this->splitBooking('chardupdep@example.com');
        $inquiry->update([
            'payment_pending_amount' => '1000.00',
            'paymongo_session_id' => 'cs_char_dupdep',
        ]);

        $this->fakeCheckout($inquiry->refresh(), 'cs_char_dupdep', 'pay_char_dupdep', 100000);

        $service = app(PaymentReconciliationService::class);
        $this->assertSame('recorded', $service->reconcileByCheckoutId('cs_char_dupdep')['outcome']);
        // Not fully paid, same provider payment id: duplicate_payment (no double credit).
        $this->assertSame('duplicate_payment', $service->reconcileByCheckoutId('cs_char_dupdep')['outcome']);
        $this->assertSame('1000.00', (string) $inquiry->refresh()->amount_paid);
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    // ---- Late payment ----

    public function test_late_payment_on_cancelled_is_refunded_not_recorded(): void
    {
        $inquiry = $this->confirmedBooking('charlate@example.com');
        $this->withSession($this->portalSession($inquiry))->post(route('booking.portal.cancel', $inquiry));
        $this->assertSame('cancelled', $inquiry->refresh()->status);
        $inquiry->update(['paymongo_session_id' => 'cs_char_late']);
        $inquiry->refresh();
        $centavos = (int) round((float) $inquiry->total_amount * 100);

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->checkoutSessionResponse($inquiry, 'cs_char_late', 'pay_char_late', $centavos),
            ], 200),
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_char_late']], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_late');

        $this->assertSame('late_payment', $result['outcome']);
        $this->assertSame('refunded', $result['refund']);

        $inquiry->refresh();
        $this->assertSame('cancelled', $inquiry->status);
        $this->assertSame('0.00', (string) ($inquiry->amount_paid ?? '0.00'));
        $this->assertNull($inquiry->paymongo_payment_id);

        $late = Payment::where('provider_payment_id', 'pay_char_late')->firstOrFail();
        $this->assertSame(Payment::STATUS_REFUNDED, $late->status);
        $this->assertSame(
            'rfnd_char_late',
            Payment::where('inquiry_id', $inquiry->id)->where('type', Payment::TYPE_REFUND)->firstOrFail()->provider_refund_id
        );
    }

    public function test_failed_late_refund_leaves_requires_refund(): void
    {
        $inquiry = $this->confirmedBooking('charlatefail@example.com');
        $this->withSession($this->portalSession($inquiry))->post(route('booking.portal.cancel', $inquiry));
        $inquiry->update(['paymongo_session_id' => 'cs_char_late_fail']);
        $inquiry->refresh();
        $centavos = (int) round((float) $inquiry->total_amount * 100);

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => $this->checkoutSessionResponse($inquiry, 'cs_char_late_fail', 'pay_char_late_fail', $centavos),
            ], 200),
            'api.paymongo.com/v1/refunds' => Http::response(['errors' => ['boom']], 500),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_late_fail');

        $this->assertSame('late_payment', $result['outcome']);
        $this->assertSame('failed', $result['refund']);
        $this->assertSame(
            Payment::STATUS_REQUIRES_REFUND,
            Payment::where('provider_payment_id', 'pay_char_late_fail')->firstOrFail()->status
        );
        $this->assertSame('0.00', (string) ($inquiry->refresh()->amount_paid ?? '0.00'));
    }

    public function test_payment_on_pending_booking_is_ignored(): void
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'charpending@example.com',
            'booking_type' => 'day_tour',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-10',
            'pax' => 2,
        ]);
        $inquiry = Inquiry::where('email', 'charpending@example.com')->firstOrFail();
        $this->assertSame('pending', $inquiry->status);
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'paymongo_session_id' => 'cs_char_pend',
        ]);
        $inquiry->refresh();
        $centavos = (int) round((float) $inquiry->total_amount * 100);

        $this->fakeCheckout($inquiry, 'cs_char_pend', 'pay_char_pend', $centavos);

        $result = app(PaymentReconciliationService::class)->reconcileByCheckoutId('cs_char_pend');

        $this->assertSame('ignored', $result['outcome']);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    // ---- Stale-pending sweep ----

    public function test_sweep_clears_stale_unpaid_checkout(): void
    {
        $inquiry = $this->confirmedBooking('charsweepclear@example.com');
        $inquiry->update([
            'payment_pending_amount' => $inquiry->total_amount,
            'payment_pending_at' => now()->subHours(5),
            'paymongo_session_id' => 'cs_char_sweep_clear',
        ]);

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions/*' => Http::response([
                'data' => [
                    'id' => 'cs_char_sweep_clear',
                    'type' => 'checkout_session',
                    'attributes' => [
                        'reference_number' => $inquiry->refresh()->reference_code,
                        'currency' => 'PHP',
                        'paid_at' => null,
                        'payments' => [],
                    ],
                ],
            ], 200),
        ]);

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        $this->assertSame(1, $stats['cleared']);
        $inquiry->refresh();
        $this->assertNull($inquiry->payment_pending_amount);
        $this->assertNull($inquiry->paymongo_session_id);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_sweep_clears_legacy_pending_equal_to_total(): void
    {
        // Legacy tolerance (abs < 0.005) preserved: an exact-total legacy
        // pending clears without any remote call.
        $inquiry = $this->confirmedBooking('charsweeplegacy@example.com');
        $inquiry->update(['payment_pending_amount' => $inquiry->total_amount, 'paymongo_session_id' => null]);
        Inquiry::where('id', $inquiry->id)->update(['payment_pending_at' => null, 'updated_at' => now()->subHours(30)]);

        Http::fake();

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        $this->assertSame(1, $stats['cleared']);
        $this->assertNull($inquiry->refresh()->payment_pending_amount);
        Http::assertNothingSent();
    }

    public function test_sweep_leaves_deposit_split_legacy_pending_for_review(): void
    {
        // Clearing here would move the verification baseline from the
        // deposit to the total, so the row is reported, never mutated.
        $inquiry = $this->confirmedBooking('charsweepreview@example.com');
        $inquiry->update(['deposit_amount' => '1500.00', 'payment_pending_amount' => '1500.00', 'paymongo_session_id' => null]);
        Inquiry::where('id', $inquiry->id)->update(['payment_pending_at' => null, 'updated_at' => now()->subHours(30)]);

        Http::fake();

        $stats = app(PaymentReconciliationService::class)->sweepStalePendings();

        $this->assertSame(1, $stats['needs_review']);
        $this->assertSame('1500.00', (string) $inquiry->refresh()->payment_pending_amount);
        Http::assertNothingSent();
    }

    // ---- Payment-id and inquiry entry points ----

    public function test_reconcile_by_payment_id_matches_existing_ledger_row(): void
    {
        $inquiry = $this->confirmedBooking('charpayid@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_char_match',
        ]);
        Payment::recordPaid($inquiry->refresh(), (string) $inquiry->total_amount, 'qrph', 'pay_char_match', 'cs_char_m', Payment::TYPE_FULL);
        $centavos = (int) round((float) $inquiry->total_amount * 100);

        Http::fake([
            'api.paymongo.com/v1/payments/*' => Http::response([
                'data' => ['id' => 'pay_char_match', 'type' => 'payment', 'attributes' => ['status' => 'paid', 'amount' => $centavos, 'currency' => 'PHP']],
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByPaymentId('pay_char_match');

        $this->assertSame('matched', $result['outcome']);
        $this->assertSame($inquiry->id, $result['inquiry_id']);
    }

    public function test_reconcile_by_payment_id_reports_mismatch_without_writing(): void
    {
        $inquiry = $this->confirmedBooking('charpaymm@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_char_mm',
        ]);
        Payment::recordPaid($inquiry->refresh(), (string) $inquiry->total_amount, 'qrph', 'pay_char_mm', 'cs_char_mm', Payment::TYPE_FULL);
        $centavos = (int) round((float) $inquiry->total_amount * 100);

        Http::fake([
            'api.paymongo.com/v1/payments/*' => Http::response([
                'data' => ['id' => 'pay_char_mm', 'type' => 'payment', 'attributes' => ['status' => 'paid', 'amount' => $centavos + 100, 'currency' => 'PHP']],
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByPaymentId('pay_char_mm');

        $this->assertSame('mismatch', $result['outcome']);
        $this->assertSame($inquiry->id, $result['inquiry_id']);
        $this->assertSame(1, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_reconcile_by_payment_id_without_local_row_is_unmatched(): void
    {
        Http::fake([
            'api.paymongo.com/v1/payments/*' => Http::response([
                'data' => ['id' => 'pay_char_orphan', 'type' => 'payment', 'attributes' => ['status' => 'paid', 'amount' => 99900, 'currency' => 'PHP']],
            ], 200),
        ]);

        $result = app(PaymentReconciliationService::class)->reconcileByPaymentId('pay_char_orphan');

        $this->assertSame('unmatched', $result['outcome']);
        $this->assertSame(0, Payment::where('provider_payment_id', 'pay_char_orphan')->count());
    }

    public function test_reconcile_by_inquiry_with_nothing_remote(): void
    {
        $inquiry = $this->confirmedBooking('charnothing@example.com');

        Http::fake();

        $result = app(PaymentReconciliationService::class)->reconcileByInquiry($inquiry);

        $this->assertSame('nothing_to_reconcile', $result['outcome']);
        Http::assertNothingSent();
    }
}
