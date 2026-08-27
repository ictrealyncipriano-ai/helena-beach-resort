<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportsTest extends TestCase
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

    private function inquiry(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Export Guest',
            'email' => 'export@example.com',
            'phone' => '09170000000',
            'booking_type' => 'day_tour',
            'status' => 'confirmed',
            'source' => 'website',
            'total_amount' => 1500.00,
            'amount_paid' => 1500.00,
            'payment_method' => 'gcash',
        ], $overrides));
    }

    public function test_exports_index_lists_all_reports(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.exports.index'))
            ->assertOk()
            ->assertSee('Inquiries')
            ->assertSee('Revenue')
            ->assertSee('Guests');
    }

    public function test_inquiries_csv_streams_with_headers_and_rows(): void
    {
        $this->inquiry();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.exports.inquiries'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $body = $response->streamedContent();

        $this->assertStringContainsString('Export Guest', $body);
        $this->assertStringContainsString('export@example.com', $body);
        $this->assertStringContainsString('Reference', $body);
    }

    public function test_inquiries_csv_respects_status_filter(): void
    {
        $this->inquiry(['status' => 'confirmed']);
        $this->inquiry(['name' => 'Pending Person', 'email' => 'pending@example.com', 'status' => 'pending']);

        $body = $this->actingAs($this->admin())
            ->get(route('admin.exports.inquiries', ['status' => 'pending']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Pending Person', $body);
        $this->assertStringNotContainsString('Export Guest', $body);
    }

    public function test_revenue_csv_groups_by_period_and_cottage(): void
    {
        $cottage = Cottage::create([
            'name' => 'Beach Villa', 'capacity' => 8,
            'rate_daytour' => 1500, 'rate_overnight' => 3000,
            'is_available' => true, 'sort_order' => 1,
        ]);
        $this->inquiry(['cottage_id' => $cottage->id, 'total_amount' => 1500, 'amount_paid' => 1500]);
        $this->inquiry(['cottage_id' => $cottage->id, 'total_amount' => 1500, 'amount_paid' => 1500]);
        $this->inquiry(['total_amount' => 5000, 'amount_paid' => 5000]); // unlinked cottage, no join row

        $body = $this->actingAs($this->admin())
            ->get(route('admin.exports.revenue'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Beach Villa', $body);
        $this->assertStringContainsString('3000', $body);
    }

    public function test_guests_csv_contains_lifetime_stats(): void
    {
        $guest = Guest::create(['name' => 'Loyal Guest', 'email' => 'loyal@example.com', 'phone' => '09170000000']);
        Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Loyal Guest',
            'email' => 'loyal@example.com',
            'guest_id' => $guest->id,
            'status' => 'confirmed',
            'source' => 'website',
            'total_amount' => 2000,
            'amount_paid' => 2000,
        ]);
        $guest->update(['total_stays' => 1, 'last_stay_at' => now()]);

        $body = $this->actingAs($this->admin())
            ->get(route('admin.exports.guests'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Loyal Guest', $body);
        $this->assertStringContainsString('loyal@example.com', $body);
        $this->assertStringContainsString('2', $body);
    }

    public function test_csv_escapes_formula_injection(): void
    {
        Guest::create(['name' => '=HYPERLINK("http://evil")', 'email' => 'evil@example.com']);

        $body = $this->actingAs($this->admin())
            ->get(route('admin.exports.guests'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString("\"'=HYPERLINK(\"\"http://evil\"\")\"", $body);
    }

    public function test_exports_require_admin_access(): void
    {
        $this->seed();
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.exports.index'))
            ->assertForbidden();
    }

    public function test_inquiries_view_renders_pdf_style_report(): void
    {
        $this->inquiry(['name' => 'Report Guest', 'email' => 'report@example.com']);

        $this->actingAs($this->admin())
            ->get(route('admin.exports.inquiries.view'))
            ->assertOk()
            ->assertSee('Inquiries Report')
            ->assertSee('Report Guest')
            ->assertSee('report@example.com')
            ->assertSee('Print / Save as PDF');
    }

    public function test_inquiries_view_respects_status_filter(): void
    {
        $this->inquiry(['name' => 'Confirmed Guest', 'status' => 'confirmed']);
        $this->inquiry(['name' => 'Pending Guest', 'status' => 'pending']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.exports.inquiries.view', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Pending Guest')
            ->assertSee('Status:</strong> Pending', false)
            ->assertDontSee('Confirmed Guest');
    }

    public function test_revenue_view_renders_totals(): void
    {
        $cottage = Cottage::create([
            'name' => 'Beach Villa', 'capacity' => 8,
            'rate_daytour' => 1500, 'rate_overnight' => 3000,
            'is_available' => true, 'sort_order' => 1,
        ]);
        $this->inquiry(['cottage_id' => $cottage->id, 'total_amount' => 1500, 'amount_paid' => 1500]);
        $this->inquiry(['cottage_id' => $cottage->id, 'total_amount' => 1500, 'amount_paid' => 1500]);

        $this->actingAs($this->admin())
            ->get(route('admin.exports.revenue.view'))
            ->assertOk()
            ->assertSee('Revenue Report')
            ->assertSee('Beach Villa')
            ->assertSee('3,000.00')
            ->assertSee('Grand Total');
    }

    public function test_guests_view_renders_lifetime_stats(): void
    {
        $guest = Guest::create(['name' => 'Loyal Guest', 'email' => 'loyal@example.com', 'phone' => '09170000000']);
        Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Loyal Guest',
            'email' => 'loyal@example.com',
            'guest_id' => $guest->id,
            'status' => 'confirmed',
            'source' => 'website',
            'total_amount' => 2000,
            'amount_paid' => 2000,
        ]);
        $guest->update(['total_stays' => 1, 'last_stay_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.exports.guests.view'))
            ->assertOk()
            ->assertSee('Guests Report')
            ->assertSee('Loyal Guest')
            ->assertSee('2,000.00');
    }

    public function test_report_views_require_admin_access(): void
    {
        $this->seed();
        $staff = User::factory()->create(['role' => 'staff']);

        foreach (['inquiries', 'revenue', 'guests'] as $report) {
            $this->actingAs($staff)
                ->get(route("admin.exports.{$report}.view"))
                ->assertForbidden();
        }
    }
}
