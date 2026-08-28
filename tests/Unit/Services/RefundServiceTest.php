<?php

namespace Tests\Unit\Services;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Services\PayMongoService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 8.8 — RefundService centralises the atomic claim-then-refund TOCTOU
 * guard that used to be duplicated in the admin InquiryController and the
 * guest BookingPortalController. These tests lock in the claim semantics.
 */
class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function paidBooking(string $email): Inquiry
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
        $inquiry->update([
            'status' => Inquiry::STATUS_CONFIRMED,
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => Inquiry::METHOD_QRPH,
            'paymongo_payment_id' => 'pay_123',
        ]);

        return $inquiry->refresh();
    }

    private function fakeRefundEndpoint(): void
    {
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_1']], 200),
        ]);
    }

    public function test_claim_and_process_refunds_and_persists_claim(): void
    {
        $this->fakeRefundEndpoint();
        $inquiry = $this->paidBooking('claim@example.com');

        $result = app(RefundService::class)->claimAndProcess($inquiry, app(PayMongoService::class));

        $this->assertSame(RefundService::CLAIMED, $result);
        $this->assertNotNull($inquiry->refresh()->refunded_at);
        Http::assertSent(fn ($req) => $req->url() === 'https://api.paymongo.com/v1/refunds');
    }

    public function test_already_claimed_does_not_call_paymongo(): void
    {
        $this->fakeRefundEndpoint();
        $inquiry = $this->paidBooking('already-claimed@example.com');
        $inquiry->update(['refunded_at' => now()]);

        $result = app(RefundService::class)->claimAndProcess($inquiry, app(PayMongoService::class));

        $this->assertSame(RefundService::ALREADY_CLAIMED, $result);
        Http::assertNothingSent();
    }

    public function test_concurrent_double_claim_only_refunds_once(): void
    {
        $this->fakeRefundEndpoint();
        $inquiry = $this->paidBooking('double@example.com');

        $service = app(RefundService::class);
        $payMongo = app(PayMongoService::class);

        $this->assertSame(RefundService::CLAIMED, $service->claimAndProcess($inquiry, $payMongo));
        $this->assertSame(RefundService::ALREADY_CLAIMED, $service->claimAndProcess($inquiry, $payMongo));

        Http::assertSentCount(1);
        Http::assertSent(fn ($req) => $req->url() === 'https://api.paymongo.com/v1/refunds');
    }

    public function test_failure_rolls_back_the_claim_and_rethrows(): void
    {
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['errors' => ['Something failed']], 500),
        ]);
        $inquiry = $this->paidBooking('fail@example.com');

        $this->expectException(\RuntimeException::class);

        try {
            app(RefundService::class)->claimAndProcess($inquiry, app(PayMongoService::class));
        } finally {
            $this->assertNull($inquiry->refresh()->refunded_at);
        }
    }

    public function test_failure_can_be_retried_after_rollback(): void
    {
        // First attempt fails with a 500, then the endpoint recovers.
        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::sequence()
                ->push(['errors' => ['Something failed']], 500)
                ->push(['data' => ['id' => 'rfnd_1']], 200),
        ]);
        $inquiry = $this->paidBooking('retry@example.com');
        $service = app(RefundService::class);
        $payMongo = app(PayMongoService::class);

        $raised = false;
        try {
            $service->claimAndProcess($inquiry, $payMongo);
        } catch (\RuntimeException) {
            $raised = true;
        }

        $this->assertTrue($raised, 'Expected the first claim to throw.');
        $this->assertNull($inquiry->refresh()->refunded_at);

        // The rollback frees the claim, so a retry can succeed.
        $this->assertSame(RefundService::CLAIMED, $service->claimAndProcess($inquiry, $payMongo));
        $this->assertNotNull($inquiry->refresh()->refunded_at);
    }
}
