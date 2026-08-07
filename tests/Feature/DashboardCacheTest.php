<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 3.10 — the admin dashboard aggregates are cached for 5 minutes and
 * must be invalidated whenever an inquiry is created/updated/deleted,
 * confirmed, cancelled, marked paid, or refunded.
 */
class DashboardCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function primeCache(): void
    {
        Cache::put(DashboardController::cacheKey(), ['totalCottages' => 1], 300);
    }

    public function test_dashboard_caches_aggregate_stats(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertTrue(Cache::has(DashboardController::cacheKey()));
    }

    public function test_storing_a_walk_in_inquiry_forgets_dashboard_cache(): void
    {
        $cottage = Cottage::first();
        $this->primeCache();
        $this->assertTrue(Cache::has(DashboardController::cacheKey()));

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.store'), [
                'name' => 'Desk Guest',
                'email' => 'desk@example.com',
                'booking_type' => 'day_tour',
                'check_in' => now()->addDays(5)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'pax' => 2,
                'cottage_id' => $cottage->id,
                'status' => 'pending',
            ])
            ->assertRedirect(route('admin.inquiries.index'));

        $this->assertFalse(Cache::has(DashboardController::cacheKey()));
    }

    public function test_confirming_an_inquiry_forgets_dashboard_cache(): void
    {
        $inquiry = Inquiry::create([
            'name' => 'Guest',
            'email' => 'confirm@example.com',
            'booking_type' => 'day_tour',
            'status' => 'pending',
            'source' => 'website',
        ]);

        $this->primeCache();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.confirm', $inquiry))
            ->assertRedirect(route('admin.inquiries.index'));

        $this->assertFalse(Cache::has(DashboardController::cacheKey()));
    }

    public function test_cancelling_an_inquiry_forgets_dashboard_cache(): void
    {
        $inquiry = Inquiry::create([
            'name' => 'Guest',
            'email' => 'cancel@example.com',
            'booking_type' => 'day_tour',
            'status' => 'pending',
            'source' => 'website',
        ]);

        $this->primeCache();

        $this->actingAs($this->admin())
            ->post(route('admin.inquiries.cancel', $inquiry))
            ->assertRedirect(route('admin.inquiries.index'));

        $this->assertFalse(Cache::has(DashboardController::cacheKey()));
    }

    public function test_deleting_an_inquiry_forgets_dashboard_cache(): void
    {
        $inquiry = Inquiry::create([
            'name' => 'Guest',
            'email' => 'delete@example.com',
            'status' => 'pending',
            'source' => 'website',
        ]);

        $this->primeCache();

        $this->actingAs($this->admin())
            ->delete(route('admin.inquiries.destroy', $inquiry))
            ->assertRedirect(route('admin.inquiries.index'));

        $this->assertFalse(Cache::has(DashboardController::cacheKey()));
    }

    public function test_guest_cancel_forgets_dashboard_cache(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Guest',
            'email' => 'guestcancel@example.com',
            'phone' => '09170000000',
            'check_in' => now()->addDays(30)->toDateString(),
            'check_out' => now()->addDays(32)->toDateString(),
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'pending',
            'source' => 'website',
        ]);

        $this->primeCache();
        $this->assertTrue(Cache::has(DashboardController::cacheKey()));

        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]])
            ->post(route('booking.portal.cancel', $inquiry))
            ->assertRedirect(route('booking.portal.show', $inquiry));

        $this->assertFalse(Cache::has(DashboardController::cacheKey()));
    }

    public function test_webhook_paid_forgets_dashboard_cache(): void
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'Guest',
            'email' => 'webhookcache@example.com',
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'source' => 'website',
            'total_amount' => 3000,
            'cottage_id' => Cottage::first()->id,
        ]);

        $this->primeCache();
        $this->assertTrue(Cache::has(DashboardController::cacheKey()));

        // PayMongo delivers the raw checkout-session resource with a paid
        // payment whose amount matches the booking total.
        $payload = json_encode([
            'data' => [
                'id' => 'cs_cache',
                'type' => 'checkout_session',
                'attributes' => [
                    'reference_number' => $inquiry->reference_code,
                    'paid_at' => 1785892089,
                    'payments' => [
                        [
                            'id' => 'pay_cache',
                            'attributes' => [
                                'status' => 'paid',
                                'amount' => (int) round($inquiry->total_amount * 100),
                                'source' => ['type' => 'qrph'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->postJson(
            route('payment.webhook'),
            json_decode($payload, true),
            ['Paymongo-Signature' => $this->signatureFor($payload)]
        )->assertOk()->assertJson(['ok' => true]);

        $this->assertFalse(Cache::has(DashboardController::cacheKey()));
    }

    private function signatureFor(string $payload): string
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'test-webhook-secret');

        return "t={$timestamp},te={$signature},li=";
    }
}
