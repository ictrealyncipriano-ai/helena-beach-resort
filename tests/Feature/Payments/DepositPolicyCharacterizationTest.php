<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\BookingEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * P1.2 deposit policy: hardened semantics (was Phase 6 WP-0 baseline).
 *
 * - deposit capped at total (request validation)
 * - confirmation requires a covered deposit (financial gate)
 * - amountDueNow() is deposit-first; isDepositPaid() is timestamp OR coverage
 * - raising deposit_amount above collected clears deposit_paid_at
 * - guest self-modify blocked at deposit-paid, not first peso
 */
class DepositPolicyCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('cloudflare');
    }

    private function makeBooking(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Deposit Guest',
            'email' => uniqid('dep').'@example.com',
            'phone' => '09170000000',
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-12',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => Inquiry::STATUS_CONFIRMED,
            'total_amount' => '5000.00',
            'source' => Inquiry::SOURCE_WEBSITE,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_guest_booking_carries_no_deposit(): void
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'nodeposit@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', 'nodeposit@example.com')->firstOrFail();

        $this->assertNull($inquiry->deposit_amount);
        $this->assertFalse($inquiry->hasDeposit());
        $this->assertFalse($inquiry->isDepositPaid());
        // With no deposit the amount due is the whole balance.
        $this->assertSame($inquiry->balanceDue(), $inquiry->amountDueNow());
        $this->assertSame((string) $inquiry->total_amount, $inquiry->amountDueNow());
    }

    public function test_admin_walk_in_rejects_deposit_above_total(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), [
                'name' => 'Walk-in Guest',
                'email' => 'bigdeposit@example.com',
                'booking_type' => 'overnight',
                'check_in' => now()->addDays(5)->toDateString(),
                'check_out' => now()->addDays(7)->toDateString(),
                'pax' => 2,
                'cottage_id' => Cottage::first()->id,
                'total_amount' => '5000.00',
                // P1.2: capped at total.
                'deposit_amount' => '6000.00',
                'status' => 'pending',
            ])
            ->assertSessionHasErrors('deposit_amount');

        $this->assertNull(Inquiry::where('email', 'bigdeposit@example.com')->first());
    }

    public function test_confirmation_requires_covered_deposit(): void
    {
        $inquiry = $this->makeBooking([
            'status' => Inquiry::STATUS_PENDING,
            'deposit_amount' => '1500.00',
            'amount_paid' => '0.00',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.confirm', $inquiry))
            ->assertSessionHas('error');

        $this->assertSame(Inquiry::STATUS_PENDING, $inquiry->refresh()->status);
        $this->assertFalse($inquiry->refresh()->isDepositPaid());

        // Covered deposit confirms fine.
        $inquiry->recordManualPayment('1500.00');

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.confirm', $inquiry))
            ->assertRedirect();

        $this->assertSame(Inquiry::STATUS_CONFIRMED, $inquiry->refresh()->status);
    }

    public function test_raising_deposit_above_collected_clears_paid_timestamp(): void
    {
        $inquiry = $this->makeBooking(['deposit_amount' => '1500.00']);
        $inquiry->recordManualPayment('1500.00');
        $this->assertNotNull($inquiry->refresh()->deposit_paid_at);

        // Admin raises the required deposit above what was collected.
        // P1.2: the stale timestamp clears so coverage decides again.
        $this->actingAs($this->admin())
            ->put(route('admin.inquiries.update', $inquiry), [
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'status' => $inquiry->status,
                'deposit_amount' => '2000.00',
            ])
            ->assertRedirect();

        $inquiry->refresh();
        $this->assertSame('2000.00', (string) $inquiry->deposit_amount);
        $this->assertNull($inquiry->deposit_paid_at);
        $this->assertFalse($inquiry->isDepositPaid());
        $this->assertSame('500.00', $inquiry->amountDueNow());
    }

    public function test_clearing_deposit_restores_balance_due(): void
    {
        $inquiry = $this->makeBooking(['deposit_amount' => '1500.00']);
        $inquiry->recordManualPayment('500.00');

        $this->assertSame('1000.00', $inquiry->refresh()->amountDueNow());

        $this->actingAs($this->admin())
            ->put(route('admin.inquiries.update', $inquiry), [
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'status' => $inquiry->status,
                'deposit_amount' => null,
            ])
            ->assertRedirect();

        $inquiry->refresh();
        $this->assertFalse($inquiry->hasDeposit());
        $this->assertSame('4500.00', $inquiry->amountDueNow());
        $this->assertSame($inquiry->balanceDue(), $inquiry->amountDueNow());
    }

    public function test_amount_due_ladder_deposit_first_then_balance(): void
    {
        $inquiry = $this->makeBooking(['deposit_amount' => '1500.00']);

        $this->assertSame('1500.00', $inquiry->amountDueNow());

        $inquiry->update(['amount_paid' => '500.00']);
        $this->assertSame('1000.00', $inquiry->refresh()->amountDueNow());

        // Deposit covered → due becomes the remaining balance.
        $inquiry->update(['amount_paid' => '1500.00']);
        $this->assertTrue($inquiry->refresh()->isDepositPaid());
        $this->assertSame('3500.00', $inquiry->refresh()->amountDueNow());

        $inquiry->update(['amount_paid' => '5000.00']);
        $this->assertSame('0.00', $inquiry->refresh()->amountDueNow());
    }

    public function test_zero_deposit_means_no_deposit(): void
    {
        $inquiry = $this->makeBooking(['deposit_amount' => '0.00']);

        $this->assertFalse($inquiry->hasDeposit());
        $this->assertFalse($inquiry->isDepositPaid());
        $this->assertSame('5000.00', $inquiry->amountDueNow());
    }

    public function test_deposit_paid_via_coverage_without_timestamp(): void
    {
        // amount_paid set directly (e.g. legacy/backfill rows): coverage alone
        // satisfies the deposit even with no timestamp recorded.
        $inquiry = $this->makeBooking(['deposit_amount' => '1500.00']);
        $inquiry->update(['amount_paid' => '2000.00']);

        $inquiry->refresh();
        $this->assertNull($inquiry->deposit_paid_at);
        $this->assertTrue($inquiry->isDepositPaid());
    }

    public function test_over_deposit_due_exceeds_balance(): void
    {
        // Validation allows deposit > total; due-now follows the deposit.
        $inquiry = $this->makeBooking(['deposit_amount' => '6000.00']);

        $this->assertSame('6000.00', $inquiry->amountDueNow());
        $this->assertSame('5000.00', $inquiry->balanceDue());
    }

    public function test_manual_payment_derives_deposit_then_full_timestamps(): void
    {
        $inquiry = $this->makeBooking(['deposit_amount' => '1500.00']);

        $inquiry->recordManualPayment('500.00');
        $inquiry->refresh();
        $this->assertNull($inquiry->deposit_paid_at);
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertFalse($inquiry->isDepositPaid());

        $inquiry->recordManualPayment('1000.00');
        $inquiry->refresh();
        $this->assertNotNull($inquiry->deposit_paid_at);
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertFalse($inquiry->isPaid());

        $inquiry->recordManualPayment('3500.00');
        $inquiry->refresh();
        $this->assertNotNull($inquiry->fully_paid_at);
        $this->assertTrue($inquiry->isPaid());
    }

    public function test_only_deposit_paid_blocks_guest_self_modify(): void
    {
        $eligibility = app(BookingEligibility::class);

        $unpaid = $this->makeBooking();
        $this->assertTrue($eligibility->canModify($unpaid));

        // P1.2: a partial payment below the deposit still allows self-modify.
        $partial = $this->makeBooking(['deposit_amount' => '1500.00']);
        $partial->update(['amount_paid' => '500.00']);
        $this->assertTrue($eligibility->canModify($partial->refresh()));

        // Covered deposit moves modification to "contact the resort".
        $paid = $this->makeBooking(['deposit_amount' => '1500.00']);
        $paid->update(['amount_paid' => '1500.00']);

        $this->assertFalse($eligibility->canModify($paid->refresh()));
        $this->assertSame(
            'To change the dates or cottage of a paid booking, please contact the resort.',
            $eligibility->cannotModifyReason($paid->refresh())
        );
    }

    public function test_proof_approval_without_amount_records_amount_due_now(): void
    {
        // The admin approve form defaults to amountDueNow(): with an unpaid
        // deposit that means the deposit, not the balance.
        $inquiry = $this->makeBooking(['deposit_amount' => '1500.00']);
        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]]);

        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->image('gcash.png', 800, 600),
        ])->assertRedirect();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.payment-proof.approve', $inquiry))
            ->assertRedirect();

        $inquiry->refresh();
        $this->assertSame('1500.00', (string) $inquiry->amount_paid);
        $this->assertNotNull($inquiry->deposit_paid_at);
        $this->assertNull($inquiry->fully_paid_at);
    }
}
