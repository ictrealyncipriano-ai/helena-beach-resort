<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Gallery;
use App\Models\Inquiry;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 8 — code quality / tech-debt behavior guards.
 */
class Phase8CodeQualityTest extends TestCase
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

        $this->actingAs($this->admin())->post(route('admin.inquiries.confirm', $inquiry));

        return $inquiry->refresh();
    }

    private function portalSession(Inquiry $inquiry): array
    {
        return ['booking_access_tokens' => [$inquiry->id => $inquiry->token]];
    }

    public function test_shared_availability_rule_rejects_blocked_dates(): void
    {
        $cottage = Cottage::first();
        $blocker = Inquiry::create([
            'reference_code' => 'HB-BLOCK3',
            'name' => 'Blocker',
            'email' => 'blocker3@example.com',
            'check_in' => '2026-09-20',
            'check_out' => '2026-09-22',
            'cottage_id' => $cottage->id,
            'source' => 'website',
        ]);
        $blocker->reserveBlocks();

        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-20',
            'check_out' => '2026-09-22',
            'pax' => 2,
        ])->assertSessionHasErrors('check_in');
    }

    public function test_admin_inquiry_store_rejects_inverted_check_out(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), [
                'name' => 'Walk-in Guest',
                'email' => 'walkin@example.com',
                'booking_type' => 'overnight',
                'check_in' => '2026-09-10',
                'check_out' => '2026-09-05',
                'pax' => 2,
                'status' => 'pending',
            ])
            ->assertSessionHasErrors('check_out');

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_invoice_falls_back_to_total_amount_when_cottage_missing(): void
    {
        $cottage = Cottage::create([
            'name' => 'Beach Villa',
            'capacity' => 10,
            'rate_daytour' => 1500,
            'rate_overnight' => 3000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $inquiry = Inquiry::create([
            'reference_code' => 'HB-INV1',
            'name' => 'Guest',
            'email' => 'inv@example.com',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'cottage_id' => $cottage->id,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'total_amount' => 6000,
            'source' => 'website',
        ]);

        // Soft-delete the cottage so the invoice can no longer read its rate.
        $cottage->delete();

        $this->withSession($this->portalSession($inquiry))
            ->get(route('invoice.show', $inquiry))
            ->assertOk()
            ->assertSee('6,000.00');
    }

    public function test_gallery_compression_uses_cloudflare_disk(): void
    {
        Storage::fake('cloudflare');
        Storage::fake('local');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        Storage::disk('cloudflare')->put('gallery/test.png', $png);

        Gallery::create([
            'title' => 'Test',
            'photo_path' => 'gallery/test.png',
            'category' => 'Resort',
            'is_active' => true,
        ]);

        // Compression must read/write the cloudflare disk (where uploads live),
        // never the default/local disk.
        $this->assertTrue(Storage::disk('cloudflare')->exists('gallery/test.png'));
        $this->assertFalse(Storage::disk('local')->exists('gallery/test.png'));
    }

    public function test_pay_redirects_with_error_when_checkout_url_missing(): void
    {
        $inquiry = $this->confirmedBooking('nourl@example.com');

        Http::fake([
            'api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => ['id' => 'cs_nourl', 'attributes' => []],
            ], 200),
        ]);

        $this->withSession($this->portalSession($inquiry))
            ->post(route('payment.pay', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry))
            ->assertSessionHas('error');

        $this->assertNull($inquiry->refresh()->paymongo_session_id);
    }

    /** 8.1.1 — AdminController class deleted */
    public function test_admin_controller_class_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/AdminController.php'));
    }

    /** 8.1.2 — PromoCode::isFixed() removed */
    public function test_promo_code_is_fixed_method_removed(): void
    {
        $this->assertFalse(method_exists(PromoCode::class, 'isFixed'));
    }

    /** 8.2.1 — cannotCancelReason returns reason for cancelled status */
    public function test_cancelled_booking_cannot_be_cancelled(): void
    {
        $inquiry = $this->confirmedBooking('cancel-test@example.com');
        $inquiry->update(['status' => 'cancelled']);

        $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', $inquiry))
            ->assertSee('no longer be cancelled');
    }

    /** 8.2.2 — cannotModifyReason returns reason for expired status */
    public function test_expired_booking_cannot_be_modified(): void
    {
        $inquiry = $this->confirmedBooking('modify-test@example.com');
        $inquiry->update(['status' => 'expired']);

        $this->withSession($this->portalSession($inquiry))
            ->get(route('booking.portal.show', $inquiry))
            ->assertSee('no longer be modified');
    }

    /** 8.2.5 — Post cover_image upload max size enforced */
    public function test_post_cover_image_max_size_enforced(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.posts.store'), [
                'title' => 'Test Post',
                'cover_image' => \Illuminate\Http\UploadedFile::fake()->create('big.png', 6000, 'image/png'),
            ])
            ->assertSessionHasErrors('cover_image');
    }

    /** 8.2.6 — Admin InquiryRequest phone max 20 */
    public function test_admin_inquiry_phone_max_20(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), [
                'name' => 'Guest',
                'email' => 'phone-test@example.com',
                'phone' => str_repeat('1', 21),
                'status' => 'pending',
            ])
            ->assertSessionHasErrors('phone');
    }

    /** 8.2.7 — InquiryRequest pax max 50 */
    public function test_inquiry_pax_max_50(): void
    {
        $this->post('/contact', [
            'name' => 'Guest',
            'email' => 'pax-test@example.com',
            'message' => 'Test booking with too many pax',
            'pax' => 51,
        ])->assertSessionHasErrors('pax');
    }

    /** 8.2.9 — Pagination preserves query string */
    public function test_admin_inquiries_pagination_preserves_query_string(): void
    {
        // Create 20 inquiries to ensure there are 2 pages
        for ($i = 0; $i < 20; $i++) {
            Inquiry::create([
                'reference_code' => "HB-PAGE{$i}",
                'name' => "Guest {$i}",
                'email' => "page{$i}@example.com",
                'source' => 'website',
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.inquiries.index', ['search' => 'Guest']))
            ->assertOk()
            ->assertSee('page=2');
    }
}