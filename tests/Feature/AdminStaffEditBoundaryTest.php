<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Slice 1 — authorization / privilege boundary.
 *
 * Read-only staff keep inquiry/dashboard read access but must not reach
 * edit forms (which authorize 'update') or trigger the PayMongo resync
 * (manager-only via policy + middleware write gate).
 */
class AdminStaffEditBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function confirmedBooking(string $email): Inquiry
    {
        $this->post('/book', [
            'name' => 'Guest',
            'email' => $email,
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'pax' => 2,
        ]);

        $inquiry = Inquiry::where('email', $email)->first();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    public function test_staff_cannot_load_inquiry_edit_form(): void
    {
        $inquiry = $this->confirmedBooking('staff-edit-inquiry@example.com');
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.inquiries.edit', $inquiry))
            ->assertForbidden();
    }

    public function test_staff_keeps_inquiry_read_access(): void
    {
        $inquiry = $this->confirmedBooking('staff-read-inquiry@example.com');
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.inquiries.index'))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk();
    }

    public function test_staff_cannot_load_guest_or_cottage_edit_forms(): void
    {
        $this->confirmedBooking('staff-edit-other@example.com');
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.guests.edit', Guest::first()))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('admin.cottages.edit', Cottage::first()))
            ->assertForbidden();
    }

    public function test_staff_cannot_trigger_payment_resync(): void
    {
        $inquiry = $this->confirmedBooking('staff-edit-resync@example.com');
        $staff = User::factory()->create(['role' => 'staff']);

        Http::fake();

        $this->actingAs($staff)
            ->post(route('admin.inquiries.resync-payment', $inquiry))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_manager_can_load_all_edit_forms(): void
    {
        $inquiry = $this->confirmedBooking('manager-edit@example.com');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.inquiries.edit', $inquiry))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.guests.edit', Guest::first()))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.cottages.edit', Cottage::first()))
            ->assertOk();
    }
}
