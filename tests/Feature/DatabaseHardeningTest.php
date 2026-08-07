<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\CottageDateBlock;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cottage(): Cottage
    {
        return Cottage::first();
    }

    private function pendingInquiry(string $email = 'hardening@example.com', string $checkIn = '2026-09-01', string $checkOut = '2026-09-03'): Inquiry
    {
        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Hardening Guest',
            'email' => $email,
            'phone' => '09170000000',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'cottage_id' => $this->cottage()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'pending',
            'source' => 'website',
        ]);
    }

    public function test_reserve_blocks_carry_inquiry_id(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->pendingInquiry();

        $inquiry->reserveBlocks();

        foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $date) {
            $this->assertDatabaseHas('cottage_date_blocks', [
                'cottage_id' => $cottage->id,
                'date' => $date,
                'inquiry_id' => $inquiry->id,
                'reason' => "Pending: {$inquiry->reference_code}",
            ]);
        }
    }

    public function test_book_blocks_carry_inquiry_id(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->pendingInquiry();

        $inquiry->bookBlocks();

        foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $date) {
            $this->assertDatabaseHas('cottage_date_blocks', [
                'cottage_id' => $cottage->id,
                'date' => $date,
                'inquiry_id' => $inquiry->id,
                'reason' => "Booked: {$inquiry->reference_code}",
            ]);
        }
    }

    public function test_upsert_creates_all_nights_in_one_stay(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->pendingInquiry('allnights@example.com');

        $inquiry->reserveBlocks();
        $inquiry->bookBlocks();

        $this->assertSame(3, CottageDateBlock::where('cottage_id', $cottage->id)
            ->where('inquiry_id', $inquiry->id)
            ->count());

        // No stray rows outside the stay range, and the pending hold was
        // promoted in place rather than duplicated.
        $this->assertSame(3, CottageDateBlock::where('cottage_id', $cottage->id)->count());
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-04',
        ]);
    }

    public function test_soft_deleted_inquiry_is_excluded_from_portal_lookup_and_admin_list(): void
    {
        $inquiry = $this->pendingInquiry('trashed@example.com');
        $inquiry->delete();

        $this->assertSoftDeleted('inquiries', ['id' => $inquiry->id]);

        // Portal lookup must not resolve a trashed booking.
        $this->post(route('booking.portal.lookup.post'), [
            'email' => $inquiry->email,
            'reference_code' => $inquiry->reference_code,
        ])->assertSessionHasErrors('reference_code');

        // Admin inquiry list must not show the trashed row.
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertDontSee($inquiry->reference_code);
    }

    public function test_soft_deleted_guest_is_excluded_from_admin_list(): void
    {
        $guest = Guest::create([
            'name' => 'Trashed Guest',
            'email' => 'trashed-guest@example.com',
        ]);
        $guest->delete();

        $this->assertSoftDeleted('guests', ['id' => $guest->id]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)
            ->get(route('admin.guests.index'))
            ->assertOk()
            ->assertDontSee('Trashed Guest');
    }

    public function test_admin_inquiry_destroy_soft_deletes_inquiry_and_releases_blocks(): void
    {
        $cottage = $this->cottage();
        $inquiry = $this->pendingInquiry('destroy@example.com');
        $inquiry->reserveBlocks();

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)
            ->delete(route('admin.inquiries.destroy', $inquiry))
            ->assertRedirect(route('admin.inquiries.index'));

        $this->assertSoftDeleted('inquiries', ['id' => $inquiry->id]);
        $this->assertDatabaseMissing('cottage_date_blocks', [
            'cottage_id' => $cottage->id,
            'date' => '2026-09-01',
        ]);
    }

    public function test_admin_guests_index_renders_sql_aggregated_stats(): void
    {
        $guest = Guest::create([
            'name' => 'Stats Guest',
            'email' => 'stats@example.com',
            'phone' => '09170000001',
        ]);

        Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Stats Guest',
            'email' => 'stats@example.com',
            'guest_id' => $guest->id,
            'status' => 'confirmed',
            'source' => 'website',
            'paid_at' => now(),
            'paid_amount' => 3000,
            'total_amount' => 3000,
            'payment_method' => 'gcash',
        ]);

        Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Stats Guest',
            'email' => 'stats@example.com',
            'guest_id' => $guest->id,
            'status' => 'confirmed',
            'source' => 'website',
            'payment_failed_at' => now(),
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($admin)->get(route('admin.guests.index'));

        $response->assertOk()->assertSee('Stats Guest');

        // The embedded guestsData must be built from the SQL aggregates, not
        // from PHP-side filtering of hydrated inquiry collections. The view
        // embeds it as JSON.parse('...'), so decode it back to inspect it.
        $content = $response->getContent();
        preg_match("/guests:\s*JSON\.parse\('([^']*)'\)/", $content, $matches);
        $this->assertArrayHasKey(1, $matches);

        $guests = json_decode(json_decode('"' . $matches[1] . '"', true), true);
        $this->assertNotNull($guests);

        $guestData = collect($guests)->first(fn ($g) => ($g['id'] ?? null) === $guest->id);
        $this->assertNotNull($guestData);
        $this->assertSame(2, $guestData['inquiries_count']);
        $this->assertSame(1, $guestData['stats']['paid_count']);
        $this->assertSame(1, $guestData['stats']['failed_count']);
        $this->assertSame(0, $guestData['stats']['refunded_count']);
        $this->assertEquals(3000, $guestData['stats']['paid_amount']);
    }

    public function test_fresh_user_defaults_to_staff_role(): void
    {
        $user = User::create([
            'name' => 'New Staff Member',
            'email' => 'new-staff@example.com',
            'password' => 'password',
        ]);

        $this->assertSame('staff', $user->fresh()->role);
    }
}
