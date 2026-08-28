<?php

namespace Tests\Feature;

use App\Models\Gallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 9.2 — direct coverage for the two artisan commands that were
 * previously untested. Both move files between disks, so they are exercised
 * with Storage::fake to keep them hermetic.
 */
class ArtisanCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cloudflare_migrate_copies_missing_files(): void
    {
        Storage::fake('public');
        Storage::fake('cloudflare');

        Storage::disk('public')->put('gallery/a.jpg', 'A');
        Storage::disk('public')->put('cottages/b.jpg', 'B');

        $this->artisan('cloudflare:migrate', ['from' => 'public'])
            ->expectsOutputToContain('Migration complete')
            ->assertExitCode(0);

        $this->assertSame('A', Storage::disk('cloudflare')->get('gallery/a.jpg'));
        $this->assertSame('B', Storage::disk('cloudflare')->get('cottages/b.jpg'));
    }

    public function test_cloudflare_migrate_skips_existing_files(): void
    {
        Storage::fake('public');
        Storage::fake('cloudflare');

        Storage::disk('public')->put('gallery/a.jpg', 'A');
        // Already on the destination — must be skipped, not overwritten.
        Storage::disk('cloudflare')->put('gallery/a.jpg', 'EXISTING');

        $this->artisan('cloudflare:migrate', ['from' => 'public'])
            ->assertExitCode(0);

        $this->assertSame('EXISTING', Storage::disk('cloudflare')->get('gallery/a.jpg'));
    }

    public function test_cloudflare_migrate_handles_missing_directory(): void
    {
        Storage::fake('public');
        Storage::fake('cloudflare');

        // No gallery/ or cottages/ directory on the source -> warns, no crash.
        $this->artisan('cloudflare:migrate', ['from' => 'public'])
            ->expectsOutputToContain("Directory 'gallery' not found")
            ->assertExitCode(0);
    }

    public function test_galleries_sync_skips_when_file_exists_on_target(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::fake('cloudflare');
        config(['filesystems.default' => 'local']);

        Storage::disk('local')->put('gallery/a.jpg', 'TARGET');
        Gallery::create(['title' => 'A', 'photo_path' => 'gallery/a.jpg']);

        $this->artisan('galleries:sync')
            ->assertExitCode(0);

        $this->assertSame('TARGET', Storage::disk('local')->get('gallery/a.jpg'));
    }

    public function test_galleries_sync_copies_from_public_when_missing_on_target(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::fake('cloudflare');
        config(['filesystems.default' => 'local']);

        Storage::disk('public')->put('gallery/b.jpg', 'SOURCE');
        Gallery::create(['title' => 'B', 'photo_path' => 'gallery/b.jpg']);

        $this->artisan('galleries:sync')
            ->assertExitCode(0);

        $this->assertSame('SOURCE', Storage::disk('local')->get('gallery/b.jpg'));
    }

    public function test_galleries_sync_generates_placeholder_svg_when_missing_everywhere(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::fake('cloudflare');
        config(['filesystems.default' => 'local']);

        // Path exists nowhere -> a placeholder SVG is generated on the target.
        Gallery::create(['title' => 'Sunset', 'photo_path' => 'gallery/sunset.jpg']);

        $this->artisan('galleries:sync')
            ->assertExitCode(0);

        $svg = Storage::disk('local')->get('gallery/sunset.jpg');
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('Sunset', $svg);
    }
}
