<?php

namespace Tests\Feature;

use App\Mail\BookingCancelled;
use App\Mail\RefundReceived;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
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

        $inquiry = Inquiry::where('email', $email)->first();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    /**
     * Simulate a successful booking lookup: the session holds the inquiry's
     * non-enumerable token, which is the only thing that grants portal access.
     */
    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    public function test_pay_link_redirects_pending_booking_to_portal_with_error(): void
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'pending@example.com',
            'booking_type' => 'day_tour',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-10',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', 'pending@example.com')->first();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');
    }

    public function test_pay_creates_checkout_session_and_redirects(): void
    {
        $inquiry = $this->confirmedBooking('pay@example.com');

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/test123'],
                ],
            ], 200),
        ]);

        $response = $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry));
        $response->assertRedirect('https://checkout.paymongo.com/test123');

        Http::assertSent(function ($request) use ($inquiry) {
            $body = $request->data();

            return $request->url() === 'https://api.paymongo.com/v2/checkout_sessions'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('sk_test_test-key:'))
                && $body['data']['attributes']['reference_number'] === $inquiry->reference_code
                && $body['data']['attributes']['line_items'][0]['amount'] === (int) round($inquiry->total_amount * 100)
                && $body['data']['attributes']['payment_method_types'] === ['qrph'];
        });

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'paymongo_session_id' => 'cs_test_123',
        ]);
    }

    public function test_pay_with_zero_amount_redirects_without_calling_api(): void
    {
        $inquiry = $this->confirmedBooking('zero@example.com');
        $inquiry->update(['total_amount' => 0]);

        Http::fake();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_pay_when_already_paid_redirects_without_calling_api(): void
    {
        $inquiry = $this->confirmedBooking('paid@example.com');
        $inquiry->update(['amount_paid' => $inquiry->total_amount, 'fully_paid_at' => now(), 'payment_method' => 'gcash']);

        Http::fake();

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('success');

        Http::assertNothingSent();
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->postJson(route('payment.webhook'), ['data' => []], ['Paymongo-Signature' => 't=1,te=invalid'])
            ->assertStatus(401);
    }

    public function test_webhook_marks_confirmed_booking_as_paid(): void
    {
        $inquiry = $this->confirmedBooking('webhook@example.com');

        // PayMongo delivers the raw checkout-session resource.
        $payload = json_encode([
            'data' => [
                'id' => 'cs_abc',
                'type' => 'checkout_session',
                'attributes' => [
                    'reference_number' => $inquiry->reference_code,
                    'payments' => [
                        [
                            'id' => 'pay_123',
                            'attributes' => [
                                'status' => 'paid',
                                'amount' => (int) round($inquiry->total_amount * 100),
                                'source' => ['type' => 'qrph'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'payment_method' => 'qrph',
            'amount_paid' => (float) $inquiry->total_amount,
            'paymongo_session_id' => 'cs_abc',
        ]);

        $this->assertGreaterThan(0, (float) $inquiry->refresh()->amount_paid);
    }

    public function test_webhook_marks_paid_for_resource_delivered_without_data_wrapper(): void
    {
        $inquiry = $this->confirmedBooking('nowrap@example.com');

        // PayMongo delivers the checkout session with no outer `data` wrapper.
        $payload = json_encode([
            'id' => 'cs_nw',
            'type' => 'checkout_session',
            'attributes' => [
                'reference_number' => $inquiry->reference_code,
                'payments' => [
                    [
                        'id' => 'pay_nw',
                        'attributes' => ['status' => 'paid', 'amount' => (int) round($inquiry->total_amount * 100), 'source' => ['type' => 'qrph']],
                    ],
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'payment_method' => 'qrph',
            'amount_paid' => (float) $inquiry->total_amount,
            'paymongo_payment_id' => 'pay_nw',
            'paymongo_session_id' => 'cs_nw',
        ]);
        $this->assertGreaterThan(0, (float) $inquiry->refresh()->amount_paid);
    }

    public function test_webhook_marks_paid_for_event_envelope_payload(): void
    {
        $inquiry = $this->confirmedBooking('envelope@example.com');

        // PayMongo's standard V1 webhook event wrapper nests the resource
        // under data.attributes.data.
        $payload = json_encode([
            'data' => [
                'id' => 'evt_1',
                'type' => 'checkout_session.payment.paid',
                'attributes' => [
                    'data' => [
                        'id' => 'cs_abc',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'reference_number' => $inquiry->reference_code,
                            'payments' => [
                                [
                                    'id' => 'pay_456',
                                    'attributes' => ['status' => 'paid', 'amount' => (int) round($inquiry->total_amount * 100), 'source' => ['type' => 'qrph']],
                                ],
                            ],
                        ],
                    ],
                    'previous_data' => null,
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'payment_method' => 'qrph',
            'amount_paid' => (float) $inquiry->total_amount,
            'paymongo_payment_id' => 'pay_456',
        ]);
        $this->assertGreaterThan(0, (float) $inquiry->refresh()->amount_paid);
    }

    public function test_webhook_marks_paid_for_generic_event_envelope_payload(): void
    {
        $inquiry = $this->confirmedBooking('genericevent@example.com');

        // PayMongo's actual deliveries wrap the resource in an envelope whose
        // data.type is the generic "event" rather than the event subtype.
        $payload = json_encode([
            'data' => [
                'id' => 'evt_gen',
                'type' => 'event',
                'attributes' => [
                    'data' => [
                        'id' => 'cs_gen',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'reference_number' => $inquiry->reference_code,
                            'payments' => [
                                [
                                    'id' => 'pay_gen',
                                    'attributes' => ['status' => 'paid', 'amount' => (int) round($inquiry->total_amount * 100), 'source' => ['type' => 'qrph']],
                                ],
                            ],
                        ],
                    ],
                    'previous_data' => null,
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'payment_method' => 'qrph',
            'amount_paid' => (float) $inquiry->total_amount,
            'paymongo_payment_id' => 'pay_gen',
        ]);
        $this->assertGreaterThan(0, (float) $inquiry->refresh()->amount_paid);
    }

    public function test_webhook_is_idempotent_for_repeat_payments(): void
    {
        $inquiry = $this->confirmedBooking('repeat@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'gcash',
            'paymongo_payment_id' => 'pay_1',
        ]);

        $payload = json_encode([
            'data' => [
                'id' => 'evt_1',
                'type' => 'checkout_session.payment.paid',
                'attributes' => [
                    'data' => [
                        'id' => 'cs_abc',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'reference_number' => $inquiry->reference_code,
                            'payments' => [['id' => 'pay_1', 'attributes' => ['status' => 'paid', 'amount' => 50000, 'source' => ['type' => 'card']]]],
                        ],
                    ],
                    'previous_data' => null,
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['already_paid' => true]);

        $this->assertSame('gcash', $inquiry->refresh()->payment_method);
    }

    public function test_webhook_ignores_unrelated_events(): void
    {
        $inquiry = $this->confirmedBooking('ignore@example.com');

        $payload = json_encode([
            'data' => [
                'type' => 'checkout_session.created',
                'data' => ['attributes' => ['reference_number' => $inquiry->reference_code]],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ignored' => true]);

        $this->assertNull($inquiry->refresh()->fully_paid_at);
    }

    public function test_webhook_records_failed_payment(): void
    {
        $inquiry = $this->confirmedBooking('fail@example.com');

        $payload = json_encode([
            'data' => [
                'id' => 'evt_1',
                'type' => 'payment.failed',
                'attributes' => [
                    'data' => [
                        'id' => 'pay_123',
                        'type' => 'payment',
                        'attributes' => [
                            'external_reference_number' => $inquiry->reference_code,
                            'amount' => 10000,
                            'source' => ['type' => 'qrph'],
                        ],
                    ],
                    'previous_data' => null,
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['failed' => true]);

        $inquiry->refresh();
        $this->assertNotNull($inquiry->payment_failed_at);
        $this->assertNull($inquiry->fully_paid_at);
    }

    public function test_webhook_failed_payment_without_reference_is_ignored_safely(): void
    {
        $payload = json_encode([
            'data' => [
                'type' => 'payment.failed',
                'data' => ['attributes' => ['amount' => 10000]],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['failed' => true]);
    }

    public function test_admin_can_mark_confirmed_booking_as_paid(): void
    {
        $inquiry = $this->confirmedBooking('manual@example.com');
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.mark-paid', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'payment_method' => 'manual',
            'amount_paid' => $inquiry->total_amount,
        ]);
        $this->assertGreaterThan(0, (float) $inquiry->refresh()->amount_paid);
    }

    public function test_admin_cannot_mark_pending_booking_as_paid(): void
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'nopay@example.com',
            'booking_type' => 'day_tour',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-10',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', 'nopay@example.com')->first();
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.mark-paid', $inquiry))
            ->assertSessionHas('error');

        $this->assertNull($inquiry->refresh()->fully_paid_at);
    }

    public function test_webhook_stores_paymongo_payment_id(): void
    {
        $inquiry = $this->confirmedBooking('payid@example.com');

        $payload = json_encode([
            'data' => [
                'id' => 'evt_1',
                'type' => 'checkout_session.payment.paid',
                'attributes' => [
                    'data' => [
                        'id' => 'cs_abc',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'reference_number' => $inquiry->reference_code,
                            'payments' => [
                                [
                                    'id' => 'pay_123',
                                    'attributes' => ['status' => 'paid', 'amount' => (int) round($inquiry->total_amount * 100), 'source' => ['type' => 'qrph']],
                                ],
                            ],
                        ],
                    ],
                    'previous_data' => null,
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk();

        $this->assertDatabaseHas('inquiries', [
            'id' => $inquiry->id,
            'paymongo_payment_id' => 'pay_123',
        ]);
    }

    public function test_refund_service_calls_paymongo_refunds_api(): void
    {
        $inquiry = $this->confirmedBooking('refundsvc@example.com');
        $inquiry->update([
            'amount_paid' => 250.00,
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_123',
        ]);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_1']], 200),
        ]);

        $result = app(PayMongoService::class)->refund($inquiry);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.paymongo.com/v1/refunds'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('sk_test_test-key:'))
                && $body['data']['attributes']['payment_id'] === 'pay_123'
                && $body['data']['attributes']['amount'] === 25000
                && $body['data']['attributes']['reason'] === 'requested_by_customer';
        });

        $this->assertSame('rfnd_1', $result['id']);
    }

    public function test_admin_refund_refunds_and_cancels_booking(): void
    {
        Mail::fake();
        $inquiry = $this->confirmedBooking('adminrefund@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_123',
        ]);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_1']], 200),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.refund', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $inquiry->refresh();
        $this->assertSame('cancelled', $inquiry->status);
        $this->assertNotNull($inquiry->refunded_at);
        $this->assertSame((float) $inquiry->total_amount, (float) $inquiry->refund_amount);
        $this->assertSame(0, $inquiry->guest->fresh()->total_stays);
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
        ]);

        Mail::assertSent(RefundReceived::class, fn ($mailable) => $mailable->hasTo($inquiry->email));
    }

    public function test_admin_cannot_refund_unpaid_booking(): void
    {
        $inquiry = $this->confirmedBooking('norefund@example.com');
        $admin = User::factory()->create(['role' => 'super_admin']);

        Http::fake();
        $this->actingAs($admin)
            ->post(route('admin.inquiries.refund', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertNull($inquiry->refresh()->refunded_at);
    }

    public function test_admin_cannot_refund_twice(): void
    {
        $inquiry = $this->confirmedBooking('twice@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_123',
            'refunded_at' => now(),
            'refund_amount' => $inquiry->total_amount,
        ]);
        $admin = User::factory()->create(['role' => 'super_admin']);

        Http::fake();
        $this->actingAs($admin)
            ->post(route('admin.inquiries.refund', $inquiry))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_guest_cancel_refunds_paid_booking(): void
    {
        Mail::fake();
        $inquiry = $this->confirmedBooking('guestrefund@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_123',
        ]);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_1']], 200),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        $inquiry->refresh();
        $this->assertSame('cancelled', $inquiry->status);
        $this->assertNotNull($inquiry->refunded_at);
        $this->assertSame((float) $inquiry->total_amount, (float) $inquiry->refund_amount);

        Mail::assertSent(RefundReceived::class, fn ($mailable) => $mailable->hasTo($inquiry->email));
        Mail::assertSent(BookingCancelled::class, fn ($mailable) => $mailable->hasTo($inquiry->email));
    }

    public function test_guest_cancel_paid_booking_refund_failure_still_cancels(): void
    {
        $inquiry = $this->confirmedBooking('refundfail@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_123',
        ]);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['errors' => ['Something failed']], 500),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        $inquiry->refresh();
        $this->assertSame('cancelled', $inquiry->status);
        $this->assertNull($inquiry->refunded_at);
    }

    public function test_guest_cancel_unpaid_booking_does_not_call_refund(): void
    {
        $inquiry = $this->confirmedBooking('unpaidcancel@example.com');

        Http::fake();
        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        Http::assertNothingSent();
        $this->assertSame('cancelled', $inquiry->refresh()->status);
        $this->assertNull($inquiry->refresh()->refunded_at);
    }

    public function test_webhook_rejects_stale_timestamp_signature(): void
    {
        $inquiry = $this->confirmedBooking('stale@example.com');

        $payload = json_encode([
            'data' => [
                'id' => 'cs_stale',
                'type' => 'checkout_session',
                'attributes' => [
                    'reference_number' => $inquiry->reference_code,
                    'payments' => [
                        ['id' => 'pay_stale', 'attributes' => ['status' => 'paid', 'amount' => (int) round($inquiry->total_amount * 100), 'source' => ['type' => 'qrph']]],
                    ],
                ],
            ],
        ]);

        // Validly signed, but the `t` timestamp is an hour old → rejected.
        $stale = time() - 3600;
        $signature = hash_hmac('sha256', $stale.'.'.$payload, 'test-webhook-secret');

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => "t={$stale},te={$signature},li="]
        )->assertStatus(401);

        $this->assertNull($inquiry->refresh()->fully_paid_at);
    }

    public function test_webhook_does_not_mark_paid_on_amount_mismatch(): void
    {
        $inquiry = $this->confirmedBooking('mismatch@example.com');

        // Signed payload with a valid timestamp but a WRONG paid amount.
        $payload = json_encode([
            'data' => [
                'id' => 'cs_mm',
                'type' => 'checkout_session',
                'attributes' => [
                    'reference_number' => $inquiry->reference_code,
                    'payments' => [
                        ['id' => 'pay_mm', 'attributes' => ['status' => 'paid', 'amount' => 1, 'source' => ['type' => 'qrph']]],
                    ],
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertStatus(400)->assertJson(['error' => 'Payment amount mismatch']);

        $inquiry->refresh();
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertNull($inquiry->paymongo_payment_id);
    }

    public function test_webhook_marks_paid_when_amount_matches(): void
    {
        $inquiry = $this->confirmedBooking('match@example.com');

        $payload = json_encode([
            'data' => [
                'id' => 'cs_match',
                'type' => 'checkout_session',
                'attributes' => [
                    'reference_number' => $inquiry->reference_code,
                    'payments' => [
                        ['id' => 'pay_match', 'attributes' => ['status' => 'paid', 'amount' => (int) round($inquiry->total_amount * 100), 'source' => ['type' => 'qrph']]],
                    ],
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertGreaterThan(0, (float) $inquiry->refresh()->amount_paid);
    }

    public function test_guest_cancel_issues_single_refund_for_duplicate_requests(): void
    {
        $inquiry = $this->confirmedBooking('idem@example.com');
        $inquiry->update([
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_123',
        ]);

        $payMongo = $this->mock(PayMongoService::class);
        $payMongo->shouldReceive('refund')->once()->andReturn(['id' => 'rfnd_1']);

        // First cancel: refund is processed.
        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        $inquiry->refresh();
        $this->assertNotNull($inquiry->refunded_at);
        $this->assertSame('cancelled', $inquiry->status);

        // Simulate a concurrent second request that raced past canCancel
        // (status restored, refund already claimed): it must abort the
        // refund and not call PayMongo a second time.
        $inquiry->update(['status' => 'confirmed']);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        $payMongo->shouldHaveReceived('refund')->once();
    }

    private function signatureFor(string $payload): string
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'test-webhook-secret');

        return "t={$timestamp},te={$signature},li=";
    }
}
