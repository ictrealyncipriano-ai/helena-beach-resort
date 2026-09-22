<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 7 — authentication/security cluster (characterization-first).
 *
 * Desired end state (must FAIL pre-fix unless noted as guard):
 *  - Admin passwords require min-12 + letters + numbers + uncompromised.
 *  - Portal booking tokens carry granted_at and expire after 72h via the
 *    existing lookup/session-expired path.
 *  - Database session payloads are encrypted at rest.
 */
class AuthHardeningCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function userPayload(string $password, string $email): array
    {
        return [
            'name' => 'Hardening Target',
            'email' => $email,
            'password' => $password,
            'role' => 'staff',
        ];
    }

    public function test_short_password_is_rejected(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.users.store'), $this->userPayload('password1', 'shortpass@example.com'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'shortpass@example.com']);
    }

    public function test_breached_password_is_rejected(): void
    {
        // Fake the HIBP k-anonymity endpoint as breached for this password.
        $hash = strtoupper(sha1('Password123'));
        Http::fake([
            'api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response(substr($hash, 5).':42'),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.users.store'), $this->userPayload('Password123', 'breached@example.com'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'breached@example.com']);
    }

    public function test_strong_uncompromised_password_still_accepted(): void
    {
        Http::fake(['api.pwnedpasswords.com/range/*' => Http::response('')]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.users.store'), $this->userPayload('Str0ngPassw0rd!29', 'strongpass@example.com'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'strongpass@example.com']);
    }

    private function portalBooking(string $email): Inquiry
    {
        $inquiry = Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => 'TTL Guest',
            'email' => $email,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'cottage_id' => Cottage::first()->id,
            'pax' => 2,
            'booking_type' => 'overnight',
            'status' => 'confirmed',
            'total_amount' => '4000.00',
            'source' => 'booking',
        ]);

        return $inquiry;
    }

    public function test_fresh_booking_token_grant_is_accepted(): void
    {
        $inquiry = $this->portalBooking('freshgrant@example.com');

        $this->withSession(['booking_access_tokens' => [
            $inquiry->id => ['token' => $inquiry->token, 'granted_at' => now()->toDateTimeString()],
        ]])->get(route('booking.portal.show', $inquiry))
            ->assertOk();
    }

    public function test_backdated_booking_token_redirects_to_lookup(): void
    {
        $inquiry = $this->portalBooking('stalegrant@example.com');

        $this->withSession(['booking_access_tokens' => [
            $inquiry->id => ['token' => $inquiry->token, 'granted_at' => now()->subHours(73)->toDateTimeString()],
        ]])->get(route('booking.portal.show', $inquiry))
            ->assertRedirect(route('booking.portal.lookup'))
            ->assertSessionHas('error', 'Your booking session has expired. Please look up your booking again to continue.');
    }

    public function test_legacy_string_token_is_no_longer_honored(): void
    {
        $inquiry = $this->portalBooking('legacygrant@example.com');

        // Pre-fix shape carried no age; post-fix it must force re-lookup.
        $this->withSession(['booking_access_tokens' => [$inquiry->id => $inquiry->token]])
            ->get(route('booking.portal.show', $inquiry))
            ->assertRedirect(route('booking.portal.lookup'));
    }

    public function test_database_session_payload_is_encrypted(): void
    {
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function ($table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        config(['session.driver' => 'database']);

        $inquiry = $this->portalBooking('encsession@example.com');

        $this->withSession(['booking_access_tokens' => [
            $inquiry->id => ['token' => $inquiry->token, 'granted_at' => now()->toDateTimeString()],
        ]])->get(route('booking.portal.show', $inquiry))
            ->assertOk();

        $payload = (string) DB::table('sessions')->value('payload');

        $this->assertNotEmpty($payload);
        $this->assertStringNotContainsString($inquiry->token, $payload);
        $this->assertStringNotContainsString('encsession@example.com', $payload);
    }
}
