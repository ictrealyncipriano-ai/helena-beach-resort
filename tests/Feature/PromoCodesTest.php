<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PromoCodesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function book(array $overrides = [])
    {
        return $this->post('/book', array_merge([
            'name' => 'Guest',
            'email' => 'promo@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => Cottage::first()->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'pax' => 2,
        ], $overrides));
    }

    public function test_percent_promo_reduces_total_and_records_discount(): void
    {
        $cottage = Cottage::first();
        $subtotal = $cottage->rate_overnight * 2;
        PromoCode::create([
            'code' => 'summer20',
            'type' => 'percent',
            'value' => 20,
            'is_active' => true,
        ]);

        $this->book(['promo_code' => 'SUMMER20']);

        $promo = PromoCode::where('code', 'SUMMER20')->first();
        $inquiry = Inquiry::where('email', 'promo@example.com')->first();

        $this->assertNotNull($inquiry);
        $this->assertSame($promo->id, $inquiry->promo_code_id);
        $this->assertSame($subtotal * 0.8, (float) $inquiry->total_amount);
        $this->assertSame($subtotal * 0.2, (float) $inquiry->discount_amount);
    }

    public function test_booking_consumes_usage_limit(): void
    {
        PromoCode::create([
            'code' => 'LIMIT1',
            'type' => 'percent',
            'value' => 10,
            'usage_limit' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $this->book(['promo_code' => 'LIMIT1']);
        $promo = PromoCode::where('code', 'LIMIT1')->first();
        $this->assertSame(1, $promo->used_count);

        // A second booking with the same code is rejected.
        $this->book(['promo_code' => 'LIMIT1', 'email' => 'other@example.com', 'check_in' => '2026-10-05', 'check_out' => '2026-10-07'])
            ->assertSessionHasErrors('promo_code');

        $this->assertDatabaseMissing('inquiries', ['email' => 'other@example.com']);
    }

    public function test_invalid_promo_rejects_booking(): void
    {
        $this->book(['promo_code' => 'NOPE42'])
            ->assertSessionHasErrors('promo_code');

        $this->assertDatabaseMissing('inquiries', ['email' => 'promo@example.com']);
    }

    public function test_expired_promo_rejects_booking(): void
    {
        PromoCode::create([
            'code' => 'OLD1',
            'type' => 'percent',
            'value' => 10,
            'valid_until' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->book(['promo_code' => 'OLD1'])
            ->assertSessionHasErrors('promo_code');
    }

    public function test_promo_below_min_amount_rejects_booking(): void
    {
        PromoCode::create([
            'code' => 'MIN5K',
            'type' => 'percent',
            'value' => 10,
            'min_amount' => 5000,
            'is_active' => true,
        ]);

        $cottage = Cottage::create([
            'name' => 'Budget Cottage',
            'description' => 'Budget option',
            'rate_daytour' => 1000,
            'rate_overnight' => 1000,
            'is_available' => true,
        ]);

        $this->book([
            'promo_code' => 'MIN5K',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-11-01',
            'check_out' => '2026-11-02',
        ])->assertSessionHasErrors('promo_code');
    }

    public function test_admin_can_crud_promo_code(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.promo-codes.store'), [
                'code' => 'FLASH10',
                'type' => 'percent',
                'value' => 10,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.promo-codes.index'))
            ->assertSessionHas('success');

        $promo = PromoCode::where('code', 'FLASH10')->first();
        $this->assertNotNull($promo);

        $this->actingAs($admin)
            ->put(route('admin.promo-codes.update', $promo), [
                'code' => 'FLASH10',
                'type' => 'fixed',
                'value' => 500,
                'is_active' => 0,
            ])
            ->assertRedirect(route('admin.promo-codes.index'));

        $this->assertSame('fixed', $promo->refresh()->type);
        $this->assertSame('500.00', (string) $promo->value);
        $this->assertFalse($promo->is_active);
    }

    public function test_staff_cannot_access_promo_codes(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.promo-codes.index'))
            ->assertForbidden();
    }
}
