<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminInquiryPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function cottage(string $name = 'Beach Villa', float $dayTour = 1500, float $overnight = 3000): Cottage
    {
        return Cottage::create([
            'name' => $name,
            'capacity' => 10,
            'rate_daytour' => $dayTour,
            'rate_overnight' => $overnight,
            'is_available' => true,
            'sort_order' => 1,
        ]);
    }

    private function walkInPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Walk-in Guest',
            'email' => 'walkin@example.com',
            'phone' => '09170000000',
            'booking_type' => 'day_tour',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'pax' => 4,
            'total_amount' => '',
            'cottage_id' => null,
            'status' => 'pending',
            'message' => 'At the desk.',
        ], $overrides);
    }

    public function test_admin_can_create_walk_in_inquiry_and_links_guest(): void
    {
        $cottage = $this->cottage();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), $this->walkInPayload([
                'cottage_id' => $cottage->id,
            ]))
            ->assertRedirect(route('admin.inquiries.index'))
            ->assertSessionHas('success');

        $inquiry = Inquiry::where('email', 'walkin@example.com')->firstOrFail();
        $this->assertSame('walk-in', $inquiry->source);
        $this->assertSame('pending', $inquiry->status);
        $this->assertSame($cottage->id, $inquiry->cottage_id);

        $this->assertNotNull($inquiry->guest_id);
        $this->assertSame('Walk-in Guest', $inquiry->guest->name);
    }

    public function test_walk_in_inquiry_auto_calculates_total_when_blank(): void
    {
        $cottage = $this->cottage('Casa Marina', 1750, 3500);

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), $this->walkInPayload([
                'cottage_id' => $cottage->id,
                'booking_type' => 'day_tour',
                'total_amount' => '',
            ]));

        $this->assertSame(1750.0, (float) Inquiry::where('email', 'walkin@example.com')->firstOrFail()->total_amount);
    }

    public function test_walk_in_inquiry_respects_admin_entered_total(): void
    {
        $cottage = $this->cottage();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), $this->walkInPayload([
                'cottage_id' => $cottage->id,
                'total_amount' => '2500',
            ]));

        $this->assertSame(2500.0, (float) Inquiry::where('email', 'walkin@example.com')->firstOrFail()->total_amount);
    }

    public function test_pending_walk_in_reserves_cottage_blocks(): void
    {
        $cottage = $this->cottage();
        $date = now()->addDays(5)->toDateString();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), $this->walkInPayload([
                'cottage_id' => $cottage->id,
                'status' => 'pending',
            ]));

        $block = CottageDateBlock::where('cottage_id', $cottage->id)->where('date', $date)->firstOrFail();
        $this->assertStringContainsString('Pending:', $block->reason);
    }

    public function test_confirmed_walk_in_books_blocks_and_sends_confirmation_email(): void
    {
        Mail::fake();
        $cottage = $this->cottage();
        $date = now()->addDays(5)->toDateString();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), $this->walkInPayload([
                'cottage_id' => $cottage->id,
                'status' => 'confirmed',
            ]));

        $inquiry = Inquiry::where('email', 'walkin@example.com')->firstOrFail();

        $block = CottageDateBlock::where('cottage_id', $cottage->id)->where('date', $date)->firstOrFail();
        $this->assertStringContainsString('Booked:', $block->reason);

        Mail::assertSent(BookingConfirmed::class, fn ($mail) => $mail->hasTo('walkin@example.com'));

        $this->assertSame(1, $inquiry->guest->total_stays);
    }

    public function test_validation_failure_redirects_back_with_errors(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), $this->walkInPayload(['email' => '']))
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_inquiries_index_shows_add_button_and_walk_in_source_option(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSee('Add Inquiry', false)
            ->assertSee('Walk-In', false)
            ->assertSee('value="walk-in"', false);
    }

    public function test_inquiries_index_embeds_cottage_rates_for_auto_calc(): void
    {
        $this->cottage('Sunset Cottage', 2200, 4400);

        $this->actingAs($this->admin())
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSee('cottageRates', false)
            ->assertSee('Sunset Cottage', false)
            ->assertSee('2200', false)
            ->assertSee('4400', false);
    }

    public function test_index_filters_walk_in_inquiries_by_source(): void
    {
        Inquiry::create([
            'reference_code' => 'HB-000001',
            'name' => 'Online Booker',
            'email' => 'online@example.com',
            'booking_type' => 'day_tour',
            'status' => 'pending',
            'source' => 'website',
        ]);
        Inquiry::create([
            'reference_code' => 'HB-000002',
            'name' => 'Walk In Patron',
            'email' => 'desk@example.com',
            'booking_type' => 'day_tour',
            'status' => 'pending',
            'source' => 'walk-in',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.inquiries.index') . '?source=walk-in')
            ->assertOk()
            ->assertSee('Walk In Patron')
            ->assertDontSee('Online Booker');
    }
}
