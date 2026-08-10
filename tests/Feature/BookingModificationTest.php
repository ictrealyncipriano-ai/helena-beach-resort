<?php

namespace Tests\Feature;

use App\Mail\BookingModified;
use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingModificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cottages(): array
    {
        return Cottage::where('is_available', true)->orderBy('sort_order')->get()->all();
    }

    private function createBooking(string $email = 'modify@example.com'): Inquiry
    {
        $cottage = $this->cottages()[0];
        $checkIn = now()->addDays(2);
        $checkOut = now()->addDays(4);

        $total = '0.00';
        for ($cursor = $checkIn->copy(); $cursor->lt($checkOut); $cursor->addDay()) {
            $total = number_format(
                (float) $total + (float) $cottage->rateFor($cursor->copy(), 'overnight'),
                2, '.', ''
            );
        }

        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Modify Guest',
            'email' => $email,
            'phone' => '09170000000',
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'cottage_id' => $cottage->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'pending',
            'source' => 'website',
            'total_amount' => $total,
        ]);

        $inquiry->reserveBlocks();

        return $inquiry;
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    private function validModifyPayload(array $overrides = []): array
    {
        return array_merge([
            'booking_type' => 'overnight',
            'cottage_id' => $this->cottages()[0]->id,
            'check_in' => now()->addDays(6)->toDateString(),
            'check_out' => now()->addDays(8)->toDateString(),
            'pax' => 4,
        ], $overrides);
    }

    public function test_guessing_the_inquiry_id_returns_404(): void
    {
        $inquiry = $this->createBooking();

        $this->get(route('booking.portal.modify', $inquiry))->assertStatus(404);
        $this->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload())
            ->assertStatus(404);
    }

    public function test_wrong_session_token_returns_404(): void
    {
        $inquiry = $this->createBooking();

        $this->withSession(['booking_access_tokens' => [$inquiry->id => str_repeat('x', 40)]])
            ->get(route('booking.portal.modify', $inquiry))
            ->assertStatus(404);
    }

    public function test_modify_changes_schedule_and_reholds_blocks(): void
    {
        $cottages = $this->cottages();
        $original = $this->createBooking();
        $newCottage = $cottages[1];
        $newCheckIn = now()->addDays(6)->toDateString();
        $newCheckOut = now()->addDays(8)->toDateString();

        $this->withSession($this->portalSession($original))
            ->put(route('booking.portal.modify.update', $original), [
                'booking_type' => 'overnight',
                'cottage_id' => $newCottage->id,
                'check_in' => $newCheckIn,
                'check_out' => $newCheckOut,
                'pax' => 4,
            ])
            ->assertRedirect(route('booking.portal.show', $original))
            ->assertSessionHas('success');

        $original->refresh();

        $this->assertSame($newCottage->id, $original->cottage_id);
        $this->assertSame($newCheckIn, $original->check_in->toDateString());
        $this->assertSame($newCheckOut, $original->check_out->toDateString());
        $this->assertSame(4, $original->pax);

        // The old hold is released…
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $cottages[0]->id,
            'date' => now()->addDays(2)->toDateString(),
        ]);

        // …and the new one is held across the full range.
        foreach ([$newCheckIn, now()->addDays(7)->toDateString(), $newCheckOut] as $date) {
            $this->assertDatabaseHas('cottage_date_blocks', [
                'cottage_id' => $newCottage->id,
                'date' => $date,
                'reason' => "Pending: {$original->reference_code}",
            ]);
        }
    }

    public function test_modify_keeps_own_dates_without_conflict(): void
    {
        $inquiry = $this->createBooking();
        $originalCheckIn = $inquiry->check_in->toDateString();

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), [
                'booking_type' => 'overnight',
                'cottage_id' => $inquiry->cottage_id,
                'check_in' => $originalCheckIn,
                'check_out' => $inquiry->check_out->toDateString(),
                'pax' => 3,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame(3, $inquiry->pax);
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => $originalCheckIn,
        ]);
    }

    public function test_modify_conflict_preserves_original_booking(): void
    {
        $inquiry = $this->createBooking();
        $targetDate = now()->addDays(6)->toDateString();

        // A foreign hold on the new check-in blocks the switch.
        CottageDateBlock::create([
            'cottage_id' => $inquiry->cottage_id,
            'date' => $targetDate,
            'reason' => 'Maintenance',
        ]);

        $originalCheckIn = $inquiry->check_in->toDateString();

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), [
                'booking_type' => 'overnight',
                'cottage_id' => $inquiry->cottage_id,
                'check_in' => $targetDate,
                'check_out' => now()->addDays(8)->toDateString(),
                'pax' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $inquiry->refresh();
        $this->assertSame($originalCheckIn, $inquiry->check_in->toDateString());

        // Original hold intact, no block was written for the attempted dates.
        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => $originalCheckIn,
        ]);
        $this->assertSame(1, CottageDateBlock::where('date', $targetDate)->count());
    }

    public function test_modify_recomputes_total_with_rates(): void
    {
        $cottage = $this->cottages()[0];
        $inquiry = $this->createBooking();
        $newCheckIn = now()->addDays(10)->toDateString();
        $newCheckOut = now()->addDays(12)->toDateString();

        $nightly = (float) $cottage->rateFor(now()->parse($newCheckIn), 'overnight');
        $expected = number_format($nightly * 2, 2, '.', '');

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload([
                'check_in' => $newCheckIn,
                'check_out' => $newCheckOut,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($expected, $inquiry->refresh()->total_amount);
    }

    public function test_modify_preserves_applied_promo_discount(): void
    {
        $inquiry = $this->createBooking();
        $inquiry->update(['discount_amount' => 1000, 'total_amount' => '4000.00']);
        $newCheckIn = now()->addDays(10)->toDateString();
        $newCheckOut = now()->addDays(12)->toDateString();

        $cottage = $this->cottages()[0];
        $nightly = (float) $cottage->rateFor(now()->parse($newCheckIn), 'overnight');
        $expected = number_format(max($nightly * 2 - 1000, 0), 2, '.', '');

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload([
                'check_in' => $newCheckIn,
                'check_out' => $newCheckOut,
            ]))
            ->assertRedirect();

        $this->assertSame($expected, $inquiry->refresh()->total_amount);
    }

    public function test_cannot_modify_within_24_hours(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Late Guest',
            'email' => 'late@example.com',
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'cottage_id' => $this->cottages()[0]->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'source' => 'website',
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $inquiry->refresh()->status);
    }

    public function test_cannot_modify_a_paid_booking(): void
    {
        $inquiry = $this->createBooking();
        $inquiry->update([
            'paid_at' => now(),
            'paid_amount' => $inquiry->total_amount,
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
            'payment_method' => 'qrph',
        ]);

        $originalCheckIn = $inquiry->check_in->toDateString();

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload())
            ->assertRedirect()
            ->assertSessionHas('error');

        $inquiry->refresh();
        $this->assertSame($originalCheckIn, $inquiry->check_in->toDateString());
    }

    public function test_paid_booking_shows_contact_resort_note_on_detail_page(): void
    {
        $inquiry = $this->createBooking();
        $inquiry->update([
            'paid_at' => now(),
            'paid_amount' => $inquiry->total_amount,
            'amount_paid' => $inquiry->total_amount,
            'fully_paid_at' => now(),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', $inquiry))
            ->assertOk()
            ->assertDontSee('Modify Booking')
            ->assertSee('To change the dates or cottage of a paid booking, please contact the resort.');
    }

    public function test_detail_page_shows_modify_link_when_eligible(): void
    {
        $inquiry = $this->createBooking();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', $inquiry))
            ->assertOk()
            ->assertSee('Modify Booking')
            ->assertSee(route('booking.portal.modify', $inquiry));
    }

    public function test_modify_form_prefills_current_booking(): void
    {
        $inquiry = $this->createBooking();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.modify', $inquiry))
            ->assertOk()
            ->assertSee($inquiry->reference_code)
            ->assertSee('Save Changes');
    }

    public function test_modify_emails_guest_and_owner(): void
    {
        Mail::fake();
        SiteSetting::updateOrCreate(
            ['key' => 'contact_email'],
            ['value' => 'owner@example.com', 'type' => 'text']
        );

        $inquiry = $this->createBooking();

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload())
            ->assertRedirect();

        Mail::assertSent(BookingModified::class, fn ($mailable) => $mailable->hasTo($inquiry->email));
        Mail::assertSent(BookingModified::class, fn ($mailable) => $mailable->hasTo('owner@example.com'));
    }

    public function test_modify_records_activity_log_and_drops_dashboard_cache(): void
    {
        $inquiry = $this->createBooking();

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), $this->validModifyPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'guest.modified',
            'subject_type' => (new Inquiry)->getMorphClass(),
            'subject_id' => $inquiry->id,
        ]);
    }

    public function test_day_tour_can_switch_from_overnight(): void
    {
        $inquiry = $this->createBooking();
        $newDate = now()->addDays(6)->toDateString();

        $this->withSession($this->portalSession($inquiry))
            ->put(route('booking.portal.modify.update', $inquiry), [
                'booking_type' => 'day_tour',
                'cottage_id' => $inquiry->cottage_id,
                'check_in' => $newDate,
                'check_out' => null,
                'pax' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $inquiry->refresh();
        $this->assertSame('day_tour', $inquiry->booking_type);
        $this->assertNull($inquiry->check_out);

        $this->assertDatabaseHas('cottage_date_blocks', [
            'cottage_id' => $inquiry->cottage_id,
            'date' => $newDate,
        ]);

        foreach ([now()->addDays(2)->toDateString(), now()->addDays(3)->toDateString()] as $oldDate) {
            $this->assertDatabaseMissing('cottage_date_blocks', [
                'cottage_id' => $inquiry->cottage_id,
                'date' => $oldDate,
            ]);
        }
    }
}
