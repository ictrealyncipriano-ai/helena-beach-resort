<?php

namespace Tests\Feature\Payments;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Money Migration — manual-settlement golden characterization for
 * Inquiry::recordManualPayment().
 *
 * Pins observable boundaries BEFORE migrating the float computation to
 * Money: exact-balance acceptance, one-centavo-over rejection (the 0.005
 * over-balance gate), zero/negative rejection, idempotent-retry outcomes,
 * and deposit-leg stamping. All amounts are 2-decimal domain
 * (DB decimal 10,2); sub-cent dust is unrepresentable and unpinned.
 *
 * NOTE (contrast with the webhook path): this path classifies from the
 * PRE-write locked state, so a first full manual settlement classifies as
 * `full_payment` — preserved as is.
 */
class ManualPaymentCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function confirmedBooking(string $email, string $total = '3000.00', ?string $deposit = null): Inquiry
    {
        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Manual Guest',
            'email' => $email,
            'phone' => '09170000000',
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'total_amount' => $total,
            'deposit_amount' => $deposit,
            'source' => 'booking',
        ]);
    }

    public function test_exact_balance_accepts_and_marks_fully_paid(): void
    {
        $inquiry = $this->confirmedBooking('manfull@example.com');

        $this->assertTrue($inquiry->recordManualPayment('3000.00'));

        $inquiry->refresh();
        $this->assertSame('3000.00', (string) $inquiry->amount_paid);
        $this->assertNotNull($inquiry->fully_paid_at);

        $ledger = Payment::where('inquiry_id', $inquiry->id)->firstOrFail();
        $this->assertSame('3000.00', (string) $ledger->amount);
        $this->assertSame(Payment::STATUS_PAID, $ledger->status);
        $this->assertSame(Payment::PROVIDER_MANUAL, $ledger->provider);
        $this->assertSame(Payment::TYPE_FULL, $ledger->type);
    }

    public function test_partial_payment_leaves_balance_open(): void
    {
        $inquiry = $this->confirmedBooking('manpart@example.com');

        $this->assertFalse($inquiry->recordManualPayment('1000.00'));

        $inquiry->refresh();
        $this->assertSame('1000.00', (string) $inquiry->amount_paid);
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertSame(Payment::TYPE_BALANCE, Payment::where('inquiry_id', $inquiry->id)->firstOrFail()->type);
    }

    public function test_over_balance_by_one_centavo_rejects_without_writing(): void
    {
        $inquiry = $this->confirmedBooking('manover@example.com');

        try {
            $inquiry->recordManualPayment('3000.01');
            $this->fail('Expected ValidationException for over-balance payment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $this->assertSame('0.00', (string) $inquiry->refresh()->amount_paid);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_zero_and_negative_amounts_reject_without_writing(): void
    {
        $inquiry = $this->confirmedBooking('manzero@example.com');

        foreach (['0.00', '-5.00'] as $amount) {
            try {
                $inquiry->recordManualPayment($amount);
                $this->fail("Expected ValidationException for amount {$amount}.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('amount', $e->errors());
            }
        }

        $this->assertSame('0.00', (string) $inquiry->refresh()->amount_paid);
        $this->assertSame(0, Payment::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_idempotent_retry_returns_stored_outcome_without_double_write(): void
    {
        $full = $this->confirmedBooking('manidemfull@example.com');
        $this->assertTrue($full->recordManualPayment('3000.00', 'manual', 'key-full-1'));
        $this->assertTrue($full->recordManualPayment('3000.00', 'manual', 'key-full-1'));
        $this->assertSame(1, Payment::where('inquiry_id', $full->id)->count());

        $partial = $this->confirmedBooking('manidempart@example.com');
        $this->assertFalse($partial->recordManualPayment('1000.00', 'manual', 'key-part-1'));
        $this->assertFalse($partial->recordManualPayment('1000.00', 'manual', 'key-part-1'));
        $this->assertSame('1000.00', (string) $partial->refresh()->amount_paid);
        $this->assertSame(1, Payment::where('inquiry_id', $partial->id)->count());
    }

    public function test_deposit_leg_stamps_deposit_without_full_payment(): void
    {
        $inquiry = $this->confirmedBooking('mandep@example.com', '5000.00', '1000.00');

        $this->assertFalse($inquiry->recordManualPayment('1000.00'));

        $inquiry->refresh();
        $this->assertSame('1000.00', (string) $inquiry->amount_paid);
        $this->assertNotNull($inquiry->deposit_paid_at);
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertSame(Payment::TYPE_DEPOSIT, Payment::where('inquiry_id', $inquiry->id)->firstOrFail()->type);
    }
}
