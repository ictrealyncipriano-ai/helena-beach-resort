<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\InquiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function walkInPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Walk-in Guest',
            'email' => 'walkin@example.com',
            'phone' => '09170000000',
            'booking_type' => 'day_tour',
            'check_in' => now()->addDays(12)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'pax' => 2,
            'total_amount' => '',
            'cottage_id' => null,
            'status' => 'pending',
            'message' => 'At the desk.',
        ], $overrides);
    }

    public function test_website_store_reuses_existing_guest_regardless_of_email_case(): void
    {
        Mail::fake();
        $existing = Guest::create(['name' => 'Maria Santos', 'email' => 'Maria@Example.com']);

        $inquiry = app(InquiryService::class)->store([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'booking_type' => 'day_tour',
        ]);

        $this->assertSame($existing->id, $inquiry->guest_id);
        $this->assertDatabaseCount('guests', 1);
        $this->assertSame('Maria@Example.com', $existing->fresh()->email);
    }

    public function test_website_store_creates_new_guest_with_normalized_email(): void
    {
        Mail::fake();

        $inquiry = app(InquiryService::class)->store([
            'name' => 'New Guest',
            'email' => '  NEW@Example.com  ',
            'booking_type' => 'day_tour',
        ]);

        $inquiry->load('guest');
        $this->assertSame('new@example.com', $inquiry->guest->email);
        $this->assertDatabaseCount('guests', 1);
    }

    public function test_website_store_restores_trashed_guest_with_case_variant_email(): void
    {
        Mail::fake();
        $existing = Guest::create(['name' => 'Tan', 'email' => 'Tan@Example.com']);
        $existing->delete();

        $inquiry = app(InquiryService::class)->store([
            'name' => 'Tan',
            'email' => 'tan@example.com',
            'booking_type' => 'day_tour',
        ]);

        $this->assertSame($existing->id, $inquiry->guest_id);
        $this->assertNull($existing->fresh()->deleted_at);
        $this->assertDatabaseCount('guests', 1);
    }

    public function test_admin_walk_in_reuses_existing_guest_regardless_of_email_case(): void
    {
        $existing = Guest::create(['name' => 'Old Name', 'email' => 'Juan@Example.com']);

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), $this->walkInPayload([
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com',
                'phone' => '09170000000',
            ]))
            ->assertRedirect(route('admin.inquiries.index'))
            ->assertSessionHas('success');

        $inquiry = Inquiry::where('email', 'juan@example.com')->firstOrFail();
        $this->assertSame($existing->id, $inquiry->guest_id);
        $this->assertDatabaseCount('guests', 1);

        // Admin-entered name/phone are trusted and refreshed on the profile.
        $this->assertSame('Juan Dela Cruz', $existing->fresh()->name);
        $this->assertSame('09170000000', $existing->fresh()->phone);
    }
}
