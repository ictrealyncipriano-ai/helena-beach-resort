<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Slice 2 — payment-proof privacy boundary (characterization-first).
 *
 * Proofs are guest PII (bank/GCash receipts). They must never be served
 * from a stable unauthenticated URL, replacement must clean up the prior
 * object, and a failed replacement must leave the valid proof intact.
 */
class PaymentProofPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('cloudflare');
    }

    private function confirmedBooking(string $email): Inquiry
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

    private function uploadProof(Inquiry $inquiry, string $name = 'proof.jpg'): void
    {
        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->image($name),
        ])->assertSessionHas('success');
    }

    public function test_admin_show_does_not_expose_direct_storage_url(): void
    {
        $inquiry = $this->confirmedBooking('nourl@example.com');
        $this->uploadProof($inquiry);

        $path = $inquiry->refresh()->payment_proof_path;

        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->getContent();

        // The raw object key must never appear as a retrievable URL.
        $this->assertStringNotContainsString($path, $html);
        // The proof is served through the authorized route instead.
        $this->assertStringContainsString(
            route('admin.inquiries.payment-proof.show', $inquiry),
            $html
        );
    }

    public function test_authorized_admin_can_stream_proof(): void
    {
        $inquiry = $this->confirmedBooking('stream@example.com');
        $this->uploadProof($inquiry);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.inquiries.payment-proof.show', $inquiry))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_unauthenticated_cannot_stream_proof(): void
    {
        $inquiry = $this->confirmedBooking('nostream@example.com');
        $this->uploadProof($inquiry);
        auth()->logout();

        $this->get(route('admin.inquiries.payment-proof.show', $inquiry))
            ->assertRedirect(route('admin.login'));
    }

    public function test_replacement_deletes_previous_object(): void
    {
        $inquiry = $this->confirmedBooking('replace@example.com');
        $this->uploadProof($inquiry, 'first.jpg');
        $old = $inquiry->refresh()->payment_proof_path;
        Storage::disk('cloudflare')->assertExists($old);

        $inquiry->update([
            'payment_proof_status' => 'rejected',
            'payment_proof_reviewed_at' => now(),
            'payment_proof_review_note' => 'Unclear image',
        ]);

        $this->uploadProof($inquiry, 'second.jpg');

        $new = $inquiry->refresh()->payment_proof_path;
        $this->assertNotSame($old, $new);
        Storage::disk('cloudflare')->assertExists($new);
        Storage::disk('cloudflare')->assertMissing($old);
    }

    public function test_failed_replacement_keeps_existing_proof(): void
    {
        $inquiry = $this->confirmedBooking('failedreplace@example.com');
        $this->uploadProof($inquiry, 'valid.jpg');
        $path = $inquiry->refresh()->payment_proof_path;

        $inquiry->update([
            'payment_proof_status' => 'rejected',
            'payment_proof_reviewed_at' => now(),
        ]);

        $this->post(route('booking.portal.proof', $inquiry), [
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 100),
        ])->assertSessionHasErrors('payment_proof');

        $inquiry->refresh();
        $this->assertSame($path, $inquiry->payment_proof_path);
        $this->assertSame('rejected', $inquiry->payment_proof_status);
        Storage::disk('cloudflare')->assertExists($path);
    }
}
