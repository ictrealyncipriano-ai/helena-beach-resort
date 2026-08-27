<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('cloudflare');
    }

    private function confirmedBooking(string $email = 'manual@example.com'): Inquiry
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
            'total_amount' => '3000.00',
            'source' => 'booking',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_mark_paid_with_partial_amount_keeps_booking_unpaid(): void
    {
        $inquiry = $this->confirmedBooking();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.mark-paid', $inquiry), ['amount' => 1000])
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame('1000.00', (string) $inquiry->amount_paid);
        $this->assertFalse($inquiry->isPaid());
        $this->assertNull($inquiry->fully_paid_at);
        $this->assertSame(2000.0, (float) $inquiry->balanceDue());
    }

    public function test_second_payment_settles_the_balance_and_marks_fully_paid(): void
    {
        $inquiry = $this->confirmedBooking();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.inquiries.mark-paid', $inquiry), ['amount' => 1000]);
        $this->actingAs($admin)
            ->post(route('admin.inquiries.mark-paid', $inquiry), ['amount' => 2000])
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $inquiry->refresh();
        $this->assertTrue($inquiry->isPaid());
        $this->assertNotNull($inquiry->fully_paid_at);
        $this->assertSame('3000.00', (string) $inquiry->amount_paid);
        $this->assertSame(0.0, (float) $inquiry->balanceDue());
    }

    public function test_mark_paid_rejects_amount_above_outstanding_balance(): void
    {
        $inquiry = $this->confirmedBooking();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.mark-paid', $inquiry), ['amount' => 5000])
            ->assertSessionHasErrors('amount');

        $inquiry->refresh();
        $this->assertSame('0.00', (string) $inquiry->amount_paid);
        $this->assertNull($inquiry->refresh()->fully_paid_at);
    }

    public function test_mark_paid_is_blocked_once_fully_settled(): void
    {
        $inquiry = $this->confirmedBooking();
        $inquiry->recordManualPayment('3000.00');

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.mark-paid', $inquiry))
            ->assertSessionHas('error');

        $inquiry->refresh();
        $this->assertSame('3000.00', (string) $inquiry->amount_paid);
    }

    public function test_approving_proof_with_partial_amount_records_deposit_only(): void
    {
        $inquiry = $this->confirmedBooking();
        $inquiry->update([
            'payment_proof_path' => 'payment-proofs/deposit.jpg',
            'payment_proof_status' => 'pending',
            'payment_proof_submitted_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.payment-proof.approve', $inquiry), [
                'note' => 'Deposit received.',
                'amount' => 1500,
            ])
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('warning');

        $inquiry->refresh();
        $this->assertSame('approved', $inquiry->payment_proof_status);
        $this->assertSame('1500.00', (string) $inquiry->amount_paid);
        $this->assertFalse($inquiry->isPaid());
        $this->assertNull($inquiry->fully_paid_at);
    }

    public function test_approving_proof_without_amount_records_full_balance(): void
    {
        $inquiry = $this->confirmedBooking();
        $inquiry->update([
            'payment_proof_path' => 'payment-proofs/full.jpg',
            'payment_proof_status' => 'pending',
            'payment_proof_submitted_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.payment-proof.approve', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame('3000.00', (string) $inquiry->amount_paid);
        $this->assertTrue($inquiry->isPaid());
        $this->assertSame('manual', $inquiry->payment_method);
    }
}
