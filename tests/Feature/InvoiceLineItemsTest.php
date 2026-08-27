<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceLineItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function peakCottage(): Cottage
    {
        return Cottage::create([
            'name' => 'Invoice Villa',
            'description' => 'Peak aware',
            'rate_daytour' => 1000,
            'rate_overnight' => 2000,
            'peak_start' => '2026-12-20',
            'peak_end' => '2027-01-05',
            'peak_rate_daytour' => 1500,
            'peak_rate_overnight' => 3000,
            'is_available' => true,
        ]);
    }

    private function bookedAndConfirmed(array $overrides = []): Inquiry
    {
        $this->post('/book', array_merge([
            'name' => 'Invoice Guest',
            'email' => 'invoice@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => $this->peakCottage()->id,
            'check_in' => '2026-12-24',
            'check_out' => '2026-12-26',
            'pax' => 2,
        ], $overrides));

        $inquiry = Inquiry::where('email', 'invoice@example.com')->latest('id')->first();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    public function test_invoice_line_items_reflect_peak_night_rates(): void
    {
        $inquiry = $this->bookedAndConfirmed();

        // Dec 24 and Dec 25 are both peak nights at ₱3,000.
        $response = $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry));

        $response->assertOk()
            ->assertViewHas('subtotal', '6000.00')
            ->assertViewHas('items', function ($items) {
                return count($items) === 2
                    && collect($items)->every(fn ($item) => (float) $item['rate'] === 3000.0);
            })
            ->assertViewHas('inquiry.total_amount', '6000.00');
    }

    public function test_invoice_line_items_mix_peak_and_regular_nights(): void
    {
        $inquiry = $this->bookedAndConfirmed([
            'check_in' => '2027-01-04',
            'check_out' => '2027-01-07',
        ]);

        // Jan 4–5 are peak (₱3,000 each), Jan 6 is regular (₱2,000).
        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertViewHas('subtotal', '8000.00')
            ->assertViewHas('items', function ($items) {
                $rates = array_map(fn ($item) => (float) $item['rate'], $items);

                return count($rates) === 3 && $rates === [3000.0, 3000.0, 2000.0];
            });
    }

    public function test_day_tour_invoice_shows_single_peak_line(): void
    {
        $inquiry = $this->bookedAndConfirmed([
            'booking_type' => 'day_tour',
            'check_in' => '2026-12-24',
            'check_out' => null,
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertViewHas('subtotal', '1500.00')
            ->assertViewHas('items', function ($items) {
                return count($items) === 1 && (float) $items[0]['rate'] === 1500.0;
            });
    }

    public function test_invoice_falls_back_to_recorded_total_when_cottage_deleted(): void
    {
        $cottage = $this->peakCottage();

        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Orphan Guest',
            'email' => 'orphan@example.com',
            'phone' => '09170000000',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'total_amount' => '5000.00',
            'source' => 'booking',
        ]);
        $cottage->delete();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry->refresh()))
            ->assertOk()
            ->assertViewHas('subtotal', '5000.00')
            ->assertViewHas('items', function ($items) {
                return count($items) === 1 && (float) $items[0]['total'] === 5000.0;
            });
    }
}
