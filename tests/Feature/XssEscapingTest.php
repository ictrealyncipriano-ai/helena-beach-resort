<?php

namespace Tests\Feature;

use App\Models\Cottage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XssEscapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_page_renders_old_pax_as_integer_not_script(): void
    {
        $cottage = Cottage::create([
            'name' => 'Test Cottage',
            'slug' => 'test-cottage',
            'rate_daytour' => 1000,
            'rate_overnight' => 2000,
        ]);

        // Flash an attacker-controlled old() pax value via a failed submit.
        $this->post('/book', [
            'name' => 'Guest',
            'email' => 'x@example.com',
            'booking_type' => 'overnight',
            'cottage_id' => $cottage->id,
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-02',
            'pax' => "1');alert(1);//",
        ])->assertSessionHasErrors('pax');

        $content = $this->get(route('book'))->assertOk()->getContent();

        // The raw payload must never appear inside the <script> block.
        $this->assertStringNotContainsString("');alert(1);//", $content);
        // The integer-cast, JSON-safe value is rendered instead.
        $this->assertStringContainsString('pax: 1,', $content);
    }

    public function test_admin_user_form_escapes_injected_name(): void
    {
        $target = User::factory()->create(['name' => "';alert(1);//", 'role' => 'admin']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $content = $this->actingAs($admin)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->getContent();

        // The raw value must not appear inside the x-data attribute (which
        // would execute after HTML-attribute decoding).
        $this->assertStringNotContainsString("';alert(1);//", $content);
        // It is embedded as JSON data with hex-escaped quotes instead
        // (@js() double-encodes, so we assert on the stable fragment).
        $this->assertStringContainsString('u0027;alert(1)', $content);
        // No executable JS string-assignment remains.
        $this->assertStringNotContainsString("name: '", $content);
    }

    public function test_cottage_public_page_escapes_unsafe_description_on_save(): void
    {
        // A description containing script must be stripped at write time.
        $cottage = Cottage::create([
            'name' => 'Unsafe',
            'slug' => 'unsafe',
            'description' => '<p>Safe text</p><script>alert(1)</script>',
        ]);

        $content = $this->get(route('cottages.show', $cottage))->assertOk()->getContent();

        $this->assertStringContainsString('<p>Safe text</p>', $content);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $content);
        $this->assertDatabaseHas('cottages', [
            'id' => $cottage->id,
            'description' => '<p>Safe text</p>alert(1)',
        ]);
    }
}
