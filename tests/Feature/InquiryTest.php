<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_store_creates_inquiry_with_valid_data(): void
    {
        $cottage = Cottage::first();

        $response = $this->post('/contact', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-03',
            'pax' => 4,
            'message' => 'Looking forward to our stay!',
        ]);

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'source' => 'website',
        ]);

        $inquiry = Inquiry::where('email', 'juan@example.com')->first();
        $this->assertNotNull($inquiry->reference_code);
        $this->assertStringStartsWith('HB-', $inquiry->reference_code);

        $response->assertRedirect(route('booking.confirmation', $inquiry));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->post('/contact', [])
            ->assertSessionHasErrors(['name', 'email', 'message']);
    }

    public function test_store_validates_email_format(): void
    {
        $this->post('/contact', [
            'name' => 'Test',
            'email' => 'not-an-email',
            'message' => 'Hello',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_check_out_after_check_in(): void
    {
        $this->post('/contact', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'message' => 'Hello',
            'check_in' => '2026-08-05',
            'check_out' => '2026-08-03',
        ])->assertSessionHasErrors(['check_out']);
    }

    public function test_store_validates_existing_cottage_id(): void
    {
        $this->post('/contact', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'message' => 'Hello',
            'cottage_id' => 999,
        ])->assertSessionHasErrors(['cottage_id']);
    }

    public function test_store_redirects_to_confirmation(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'message' => 'Test message',
        ]);

        $inquiry = Inquiry::where('email', 'maria@example.com')->first();
        $response->assertRedirect(route('booking.confirmation', $inquiry));
    }

    public function test_confirmation_page_displays_inquiry(): void
    {
        $inquiry = Inquiry::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message',
            'reference_code' => 'HB-000001',
            'source' => 'website',
        ]);

        $this->get(route('booking.confirmation', $inquiry))
            ->assertStatus(200)
            ->assertSee('HB-000001');
    }
}
