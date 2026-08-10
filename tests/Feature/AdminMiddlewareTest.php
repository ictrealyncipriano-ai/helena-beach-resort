<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
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

    private function paidConfirmedBooking(string $email): Inquiry
    {
        $inquiry = $this->confirmedBooking($email);
        $inquiry->update([
            'paid_at' => now(),
            'paid_amount' => $inquiry->total_amount,
            'payment_method' => 'qrph',
            'paymongo_payment_id' => 'pay_123',
        ]);

        return $inquiry->refresh();
    }

    public function test_admin_can_view_activity_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'))
            ->assertStatus(200);
    }

    public function test_staff_is_forbidden_from_activity_logs(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.activity-logs.index'))
            ->assertStatus(403);
    }

    public function test_staff_is_forbidden_from_mark_paid(): void
    {
        $inquiry = $this->confirmedBooking('staff-mark-paid@example.com');
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->post(route('admin.inquiries.mark-paid', $inquiry))
            ->assertStatus(403);

        $this->assertNull($inquiry->refresh()->paid_at);
    }

    public function test_admin_can_mark_paid(): void
    {
        $inquiry = $this->confirmedBooking('admin-mark-paid@example.com');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.mark-paid', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $this->assertNotNull($inquiry->refresh()->paid_at);
    }

    public function test_staff_is_forbidden_from_refund(): void
    {
        $inquiry = $this->paidConfirmedBooking('staff-refund@example.com');
        $staff = User::factory()->create(['role' => 'staff']);

        Http::fake();

        $this->actingAs($staff)
            ->post(route('admin.inquiries.refund', $inquiry))
            ->assertStatus(403);

        Http::assertNothingSent();
        $this->assertNull($inquiry->refresh()->refunded_at);
    }

    public function test_admin_can_refund(): void
    {
        $inquiry = $this->paidConfirmedBooking('admin-refund@example.com');
        $admin = User::factory()->create(['role' => 'admin']);

        Http::fake([
            'api.paymongo.com/v1/refunds' => Http::response(['data' => ['id' => 'rfnd_1']], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inquiries.refund', $inquiry))
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $this->assertNotNull($inquiry->refresh()->refunded_at);
    }
}
