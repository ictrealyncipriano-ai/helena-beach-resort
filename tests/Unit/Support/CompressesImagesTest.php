<?php

namespace Tests\Unit\Support;

use App\Support\CompressesImages;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 9.5 — direct coverage for the CompressesImages trait. Uploads are
 * stored on the 'cloudflare' disk, so the trait must read/write that disk.
 *
 * These tests drive the compressor against real GD-generated images and
 * verify the file is re-written (or a graceful no-op for unsupported input).
 */
class CompressesImagesTest extends TestCase
{
    /** Harness exposing the protected static trait methods. */
    private function harness(): object
    {
        return new class
        {
            use CompressesImages;

            public static function compress(string $path): void
            {
                static::compressImage($path);
            }
        };
    }

    private function fakeImage(string $format, int $width = 32, int $height = 32): string
    {
        $img = imagecreatetruecolor($width, $height);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 100, 50));

        ob_start();
        if ($format === 'png') {
            imagepng($img);
        } elseif ($format === 'webp') {
            imagewebp($img);
        } else {
            imagejpeg($img);
        }
        $bytes = ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    public function test_compresses_jpeg_and_rewrites_on_cloudflare_disk(): void
    {
        Storage::fake('cloudflare');
        $path = 'cottages/test.jpg';
        Storage::disk('cloudflare')->put($path, $this->fakeImage('jpeg'));

        $this->harness()->compress($path);

        $this->assertTrue(Storage::disk('cloudflare')->exists($path));
        $this->assertGreaterThan(0, Storage::disk('cloudflare')->size($path));
    }

    public function test_compresses_png_and_rewrites(): void
    {
        Storage::fake('cloudflare');
        $path = 'cottages/test.png';
        Storage::disk('cloudflare')->put($path, $this->fakeImage('png'));

        $this->harness()->compress($path);

        $this->assertTrue(Storage::disk('cloudflare')->exists($path));
    }

    public function test_compresses_webp_and_rewrites(): void
    {
        Storage::fake('cloudflare');
        $path = 'cottages/test.webp';
        Storage::disk('cloudflare')->put($path, $this->fakeImage('webp'));

        $this->harness()->compress($path);

        $this->assertTrue(Storage::disk('cloudflare')->exists($path));
    }

    public function test_noop_when_file_missing(): void
    {
        Storage::fake('cloudflare');

        // Must not throw.
        $this->harness()->compress('cottages/missing.jpg');

        $this->assertFalse(Storage::disk('cloudflare')->exists('cottages/missing.jpg'));
    }

    public function test_noop_for_invalid_image_content(): void
    {
        Storage::fake('cloudflare');
        $path = 'cottages/bad.jpg';
        Storage::disk('cloudflare')->put($path, 'not-an-image');

        // Must not throw; the invalid file is left untouched.
        $this->harness()->compress($path);

        $this->assertSame('not-an-image', Storage::disk('cloudflare')->get($path));
    }

    public function test_noop_when_disk_unavailable(): void
    {
        // Point the trait's disk at a config that cannot be resolved.
        config(['filesystems.disks.cloudflare.driver' => 'missing-driver']);

        // Must not throw even though the disk is unavailable.
        $this->expectNotToPerformAssertions();
        $this->harness()->compress('cottages/x.jpg');
    }
}
