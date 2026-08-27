<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('cloudflare');
    }

    private function confirmedBooking(string $email = 'proof@example.com'): Inquiry
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Proof Guest',
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

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]]);

        return $inquiry;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_upload_requires_session_access(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'No Access',
            'email' => 'noaccess@example.com',
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'source' => 'booking',
        ]);

        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertStatus(404);
    }

    public function test_upload_rejects_non_image(): void
    {
        $inquiry = $this->confirmedBooking('nonimage@example.com');

        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 100),
        ])->assertSessionHasErrors('payment_proof');

        $this->assertSame('none', $inquiry->refresh()->payment_proof_status);
    }

    public function test_guest_can_upload_payment_proof(): void
    {
        $inquiry = $this->confirmedBooking('upload@example.com');

        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->image('gcash.png', 800, 600),
        ])->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame('pending', $inquiry->payment_proof_status);
        $this->assertNotNull($inquiry->payment_proof_path);
        $this->assertNotNull($inquiry->payment_proof_submitted_at);
        Storage::disk('cloudflare')->assertExists($inquiry->payment_proof_path);
    }

    public function test_upload_blocked_when_proof_pending(): void
    {
        $inquiry = $this->confirmedBooking('pendingproof@example.com');
        $inquiry->update([
            'payment_proof_path' => 'payment-proofs/first.jpg',
            'payment_proof_status' => 'pending',
            'payment_proof_submitted_at' => now(),
        ]);

        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->image('second.jpg'),
        ])->assertSessionHas('error');

        $this->assertSame('pending', $inquiry->refresh()->payment_proof_status);
    }

    public function test_rejected_proof_allows_reupload(): void
    {
        $inquiry = $this->confirmedBooking('reupload@example.com');
        $inquiry->update([
            'payment_proof_path' => 'payment-proofs/old.jpg',
            'payment_proof_status' => 'rejected',
            'payment_proof_submitted_at' => now()->subDay(),
            'payment_proof_reviewed_at' => now()->subDay(),
            'payment_proof_review_note' => 'Unclear image',
        ]);

        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->image('new.jpg'),
        ])->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame('pending', $inquiry->payment_proof_status);
        $this->assertNull($inquiry->payment_proof_reviewed_at);
        $this->assertNull($inquiry->payment_proof_review_note);
    }

    public function test_paid_booking_cannot_upload(): void
    {
        $inquiry = $this->confirmedBooking('paidproof@example.com');
        $inquiry->update(['amount_paid' => $inquiry->total_amount, 'fully_paid_at' => now()]);

        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertSessionHas('error');
    }

    public function test_admin_approving_proof_marks_booking_paid(): void
    {
        $inquiry = $this->confirmedBooking('approve@example.com');
        $inquiry->update([
            'payment_proof_path' => 'payment-proofs/approve.jpg',
            'payment_proof_status' => 'pending',
            'payment_proof_submitted_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.payment-proof.approve', $inquiry), [
                'note' => 'Looks good, thank you!',
            ])
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertTrue($inquiry->hasApprovedPaymentProof());
        $this->assertNotNull($inquiry->payment_proof_reviewed_at);
        $this->assertSame('Looks good, thank you!', $inquiry->payment_proof_review_note);
        $this->assertTrue($inquiry->isPaid());
        $this->assertSame('manual', $inquiry->payment_method);
    }

    public function test_admin_can_reject_pending_proof(): void
    {
        $inquiry = $this->confirmedBooking('reject@example.com');
        $inquiry->update([
            'payment_proof_path' => 'payment-proofs/reject.jpg',
            'payment_proof_status' => 'pending',
            'payment_proof_submitted_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.payment-proof.reject', $inquiry), [
                'note' => 'Receipt is not readable.',
            ])
            ->assertRedirect(route('admin.inquiries.show', $inquiry))
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame('rejected', $inquiry->payment_proof_status);
        $this->assertSame('Receipt is not readable.', $inquiry->payment_proof_review_note);
        $this->assertFalse($inquiry->isPaid());
    }

    public function test_reviewing_non_pending_proof_is_rejected(): void
    {
        $inquiry = $this->confirmedBooking('noop@example.com');

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.payment-proof.approve', $inquiry))
            ->assertSessionHas('error');

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.payment-proof.reject', $inquiry))
            ->assertSessionHas('error');
    }
}
