<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Slice 5 — admin export correctness + scalability (characterization-first).
 *
 * Desired end state (must FAIL pre-fix, except the null-date guard which
 * pins behavior that must survive the fix):
 *  - Revenue is attributed to the settlement month
 *    COALESCE(fully_paid_at, deposit_paid_at), like the dashboard.
 *  - The HTML report views paginate instead of hydrating every row.
 */
class ExportBoundaryCharacterizationTest extends TestCase
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

    private function villa(string $name): Cottage
    {
        return Cottage::create([
            'name' => $name,
            'capacity' => 4,
            'rate_daytour' => 1500,
            'rate_overnight' => 3000,
            'is_available' => true,
        ]);
    }

    private function paidBooking(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Revenue Guest',
            'email' => 'revenue-'.uniqid().'@example.com',
            'phone' => '09170000000',
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'source' => 'website',
            'cottage_id' => Cottage::first()->id,
            'total_amount' => '4000.00',
            'amount_paid' => '4000.00',
            'payment_method' => 'gcash',
        ], $overrides));
    }

    private function revenueCsv(array $filters): string
    {
        return $this->actingAs($this->admin())
            ->get(route('admin.exports.revenue', $filters))
            ->assertOk()
            ->streamedContent();
    }

    public function test_revenue_attributes_settlement_month(): void
    {
        // The revenue CSV carries no reference column; identify rows by
        // distinct cottage names.
        $this->paidBooking([
            'cottage_id' => $this->villa('Split Villa')->id,
            'deposit_paid_at' => '2026-08-10 10:00:00',
            'fully_paid_at' => '2026-09-10 10:00:00',
        ]);

        $september = $this->revenueCsv(['from' => '2026-09-01', 'to' => '2026-09-30']);
        $this->assertStringContainsString('Split Villa', $september);

        $august = $this->revenueCsv(['from' => '2026-08-01', 'to' => '2026-08-31']);
        $this->assertStringNotContainsString('Split Villa', $august);
    }

    public function test_revenue_includes_fully_paid_without_deposit_date(): void
    {
        $this->paidBooking([
            'cottage_id' => $this->villa('NoDeposit Villa')->id,
            'deposit_paid_at' => null,
            'fully_paid_at' => '2026-09-15 10:00:00',
        ]);

        $september = $this->revenueCsv(['from' => '2026-09-01', 'to' => '2026-09-30']);
        $this->assertStringContainsString('NoDeposit Villa', $september);
    }

    public function test_revenue_keeps_null_paid_date_rows_unfiltered(): void
    {
        $this->paidBooking([
            'cottage_id' => $this->villa('NoDate Villa')->id,
            'deposit_paid_at' => null,
            'fully_paid_at' => null,
        ]);

        $all = $this->revenueCsv([]);
        $this->assertStringContainsString('NoDate Villa', $all);
    }

    public function test_inquiries_report_paginates(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->paidBooking(['email' => "page-inq-{$i}@example.com"]);
        }

        $page1 = $this->actingAs($this->admin())
            ->get(route('admin.exports.inquiries.view'))
            ->assertOk()
            ->getContent();

        $page2 = $this->actingAs($this->admin())
            ->get(route('admin.exports.inquiries.view', ['page' => 2]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('page=2', $page1);
        $this->assertNotEquals($page1, $page2);
    }

    public function test_guests_report_paginates(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            Guest::create([
                'name' => "Page Guest {$i}",
                'email' => "page-guest-{$i}@example.com",
                'phone' => '09170000000',
            ]);
        }

        $page1 = $this->actingAs($this->admin())
            ->get(route('admin.exports.guests.view'))
            ->assertOk()
            ->getContent();

        $page2 = $this->actingAs($this->admin())
            ->get(route('admin.exports.guests.view', ['page' => 2]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('page=2', $page1);
        $this->assertNotEquals($page1, $page2);
    }
}
