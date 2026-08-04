<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

use App\Mail\BookingConfirmed;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function book(string $email, array $overrides = [])
    {
        return $this->post('/book', array_merge([
            'name' => 'Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ], $overrides));
    }

    public function test_booking_creates_pending_date_blocks_for_full_range(): void
    {
        $cottage = Cottage::first();

        $this->book('juan@example.com');

        $inquiry = Inquiry::where('email', 'juan@example.com')->first();

        foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $date) {
            $this->assertDatabaseHas('cottage_date_blocks', [
                'cottage_id' => $cottage->id,
                'date' => $date,
                'reason' => "Pending: {$inquiry->reference_code}",
            ]);
        }
    }

    public function test_day_tour_blocks_only_check_in_date(): void
    {
        $cottage = Cottage::first();

        $this->book('maria@example.com', [
            'booking_type' => 'day_tour',
            'check_in' => '2026-09-10',
            'check_out' => null,
        ]);

        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-10',
        ]);
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-11',
        ]);
    }

    public function test_overlapping_booking_is_rejected(): void
    {
        $this->book('first@example.com');

        $response = $this->book('second@example.com', [
            'check_in' => '2026-09-02',
            'check_out' => '2026-09-04',
        ]);

        $response->assertSessionHasErrors(['check_in']);
        $this->assertDatabaseMissing('inquiries', ['email' => 'second@example.com']);
    }

    public function test_admin_confirm_promotes_blocks_to_booked(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->book('first@example.com');
        $inquiry = Inquiry::where('email', 'first@example.com')->first();

        $this->actingAs($admin)
            ->post(route('admin.inquiries.confirm', $inquiry))
            ->assertRedirect();

        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
            'reason' => "Booked: {$inquiry->reference_code}",
        ]);
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
            'reason' => "Pending: {$inquiry->reference_code}",
        ]);
    }

    public function test_guest_cancel_releases_blocks(): void
    {
        $this->book('first@example.com');
        $inquiry = Inquiry::where('email', 'first@example.com')->first();

        $this->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect();

        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => '2026-09-01',
        ]);
    }

    public function test_admin_confirm_button_emails_guest_and_updates_stay(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->book('first@example.com');
        $inquiry = Inquiry::where('email', 'first@example.com')->first();

        $this->actingAs($admin)
            ->post(route('admin.inquiries.confirm', $inquiry))
            ->assertRedirect();

        Mail::assertSent(BookingConfirmed::class, fn ($mailable) => $mailable->hasTo($inquiry->email));
        $this->assertSame(1, $inquiry->guest->fresh()->total_stays);
    }

    public function test_edit_form_confirm_emails_guest_and_updates_stay(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->book('first@example.com');
        $inquiry = Inquiry::where('email', 'first@example.com')->first();

        $this->actingAs($admin)
            ->put(route('admin.inquiries.update', $inquiry), [
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        Mail::assertSent(BookingConfirmed::class, fn ($mailable) => $mailable->hasTo($inquiry->email));
        $this->assertSame(1, $inquiry->guest->fresh()->total_stays);
    }

    public function test_edit_form_edit_of_already_confirmed_does_not_email_again(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->book('first@example.com');
        $inquiry = Inquiry::where('email', 'first@example.com')->first();

        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry))->assertRedirect();
        Mail::assertSent(BookingConfirmed::class, 1);

        $this->actingAs($admin)
            ->put(route('admin.inquiries.update', $inquiry), [
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        Mail::assertSent(BookingConfirmed::class, 1);
        $this->assertSame(1, $inquiry->guest->fresh()->total_stays);
    }

    public function test_day_tour_confirm_button_emails_guest_without_check_out(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->book('daytour@example.com', [
            'booking_type' => 'day_tour',
            'check_in' => '2026-09-10',
            'check_out' => null,
        ]);
        $inquiry = Inquiry::where('email', 'daytour@example.com')->first();

        $this->actingAs($admin)
            ->post(route('admin.inquiries.confirm', $inquiry))
            ->assertRedirect();

        Mail::assertSent(BookingConfirmed::class, fn ($mailable) => $mailable->hasTo($inquiry->email));
    }

    public function test_day_tour_edit_form_confirm_emails_guest_without_check_out(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->book('daytour@example.com', [
            'booking_type' => 'day_tour',
            'check_in' => '2026-09-10',
            'check_out' => null,
        ]);
        $inquiry = Inquiry::where('email', 'daytour@example.com')->first();

        $this->actingAs($admin)
            ->put(route('admin.inquiries.update', $inquiry), [
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        Mail::assertSent(BookingConfirmed::class, fn ($mailable) => $mailable->hasTo($inquiry->email));
    }
}
