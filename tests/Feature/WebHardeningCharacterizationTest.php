<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Slice 12 — web hardening (characterization-first).
 *
 * Desired end state (must FAIL pre-fix unless noted as guard):
 *  - img-src is an explicit allow-list ('self', data:, blob:, configured
 *    R2 hosts) with no blanket https: source.
 * Guards (pass pre- and post-fix, pinning controls that must survive):
 *  - SVG uploads are rejected by validation on every upload path.
 *  - script-src nonce discipline is intact and the homepage renders.
 */
class WebHardeningCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('cloudflare');
    }

    private function imgSrc(): string
    {
        config(['filesystems.disks.cloudflare.url' => 'https://cdn.example.com']);

        $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy', '');

        preg_match('/img-src ([^;]+)/', $csp, $m);

        return $m[1] ?? '';
    }

    public function test_img_src_allows_blob_and_configured_r2_host(): void
    {
        $imgSrc = $this->imgSrc();

        $this->assertStringContainsString('blob:', $imgSrc);
        $this->assertStringContainsString('cdn.example.com', $imgSrc);
    }

    public function test_img_src_has_no_blanket_https_source(): void
    {
        $imgSrc = $this->imgSrc();

        $this->assertDoesNotMatchRegularExpression('/(^|\s)https:(\s|$|;)/', $imgSrc);
    }

    public function test_svg_upload_is_rejected(): void
    {
        $svg = UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.gallery.store'), [
                'title' => 'Evil',
                'photo_path' => $svg,
            ])
            ->assertSessionHasErrors('photo_path');

        Storage::disk('cloudflare')->assertMissing('gallery/evil.svg');
    }

    public function test_nonce_discipline_and_homepage_survive(): void
    {
        $response = $this->get('/')->assertOk();

        $csp = $response->headers->get('Content-Security-Policy', '');

        $this->assertStringContainsString("'nonce-", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
    }
}
