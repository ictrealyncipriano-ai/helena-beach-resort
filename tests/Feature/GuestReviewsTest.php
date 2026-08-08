<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function completedBooking(string $email = 'guest@example.com', string $type = 'overnight'): Inquiry
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Guest Reviewer',
            'email' => $email,
            'phone' => '09170000000',
            'check_in' => '2026-06-01',
            'check_out' => $type === 'day_tour' ? null : '2026-06-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => $type,
            'status' => 'confirmed',
            'source' => 'booking',
        ]);

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]]);

        return $inquiry;
    }

    public function test_guessing_review_endpoint_returns_404(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'No Access',
            'email' => 'noaccess@example.com',
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'source' => 'booking',
        ]);

        $this->post(route('booking.portal.review', $inquiry), [
            'rating' => 5,
            'content' => 'Guessed access should fail.',
        ])->assertStatus(404);
    }

    public function test_future_booking_cannot_be_reviewed(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Future Guest',
            'email' => 'future@example.com',
            'check_in' => '2026-12-01',
            'check_out' => '2026-12-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'source' => 'booking',
        ]);

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]])
            ->post(route('booking.portal.review', $inquiry), [
                'rating' => 5,
                'content' => 'Not eligible yet.',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('testimonials', ['inquiry_id' => $inquiry->id]);
    }

    public function test_unconfirmed_booking_cannot_be_reviewed(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Pending Guest',
            'email' => 'pending@example.com',
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'pending',
            'source' => 'booking',
        ]);

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]])
            ->post(route('booking.portal.review', $inquiry), [
                'rating' => 5,
                'content' => 'Not confirmed yet.',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('testimonials', ['inquiry_id' => $inquiry->id]);
    }

    public function test_completed_booking_can_be_reviewed_and_starts_inactive(): void
    {
        $inquiry = $this->completedBooking('review@example.com');

        $this->post(route('booking.portal.review', $inquiry), [
            'rating' => 4,
            'content' => 'Lovely stay, the staff were wonderful.',
        ])->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('testimonials', [
            'inquiry_id' => $inquiry->id,
            'guest_name' => 'Guest Reviewer',
            'guest_email' => 'review@example.com',
            'rating' => 4,
            'content' => 'Lovely stay, the staff were wonderful.',
            'cottage_id' => $inquiry->cottage_id,
            'source' => 'guest',
            'is_active' => false,
        ]);
    }

    public function test_review_validation_requires_rating_and_content(): void
    {
        $inquiry = $this->completedBooking('validation@example.com');

        $this->post(route('booking.portal.review', $inquiry), [
            'rating' => '',
            'content' => '',
        ])->assertSessionHasErrors(['rating', 'content']);

        $this->post(route('booking.portal.review', $inquiry), [
            'rating' => 9,
            'content' => 'Too high.',
        ])->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('testimonials', ['inquiry_id' => $inquiry->id]);
    }

    public function test_review_cannot_be_submitted_twice(): void
    {
        $inquiry = $this->completedBooking('twice@example.com');

        $this->post(route('booking.portal.review', $inquiry), [
            'rating' => 5,
            'content' => 'First review.',
        ])->assertSessionHas('success');

        $this->post(route('booking.portal.review', $inquiry), [
            'rating' => 1,
            'content' => 'Second attempt.',
        ])->assertSessionHas('error');

        $this->assertSame(1, Testimonial::where('inquiry_id', $inquiry->id)->count());
    }

    public function test_guest_review_is_hidden_until_admin_activates_it(): void
    {
        $inquiry = $this->completedBooking('visibility@example.com');

        $this->post(route('booking.portal.review', $inquiry), [
            'rating' => 5,
            'content' => 'Hidden until approved.',
        ]);

        $this->get(route('reviews'))
            ->assertOk()
            ->assertDontSee('Hidden until approved.');

        $testimonial = Testimonial::where('inquiry_id', $inquiry->id)->firstOrFail();
        $testimonial->update(['is_active' => true]);

        $this->get(route('reviews'))
            ->assertOk()
            ->assertSee('Hidden until approved.');
    }

    public function test_day_tour_completed_can_be_reviewed(): void
    {
        $inquiry = $this->completedBooking('daytour@example.com', 'day_tour');

        $this->post(route('booking.portal.review', $inquiry), [
            'rating' => 5,
            'content' => 'Great day by the beach.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('testimonials', [
            'inquiry_id' => $inquiry->id,
            'rating' => 5,
            'source' => 'guest',
        ]);
    }
}
