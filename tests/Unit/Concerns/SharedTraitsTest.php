<?php

namespace Tests\Unit\Concerns;

use App\Traits\ManagesCloudflareFiles;
use App\Traits\QueriesByMonth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 8.4 — the two shared traits introduced to DRY the admin exports and
 * cottage/gallery file cleanups. monthExpression() must stay database-agnostic
 * and deleteFromCloudflare() must tolerate null paths.
 */
class SharedTraitsTest extends TestCase
{
    private function monthHarness(): object
    {
        return new class
        {
            use QueriesByMonth;

            public function expression(string $column): string
            {
                return $this->monthExpression($column);
            }
        };
    }

    private function fileHarness(): object
    {
        return new class
        {
            use ManagesCloudflareFiles;

            public function delete(?string $path): void
            {
                $this->deleteFromCloudflare($path);
            }
        };
    }

    public function test_month_expression_for_default_driver(): void
    {
        DB::shouldReceive('getDriverName')->andReturn('mysql');

        $this->assertSame("DATE_FORMAT(created_at, '%Y-%m')", $this->monthHarness()->expression('created_at'));
    }

    public function test_month_expression_for_sqlite_driver(): void
    {
        DB::shouldReceive('getDriverName')->andReturn('sqlite');

        $this->assertSame("strftime('%Y-%m', created_at)", $this->monthHarness()->expression('created_at'));
    }

    public function test_month_expression_for_pgsql_driver(): void
    {
        DB::shouldReceive('getDriverName')->andReturn('pgsql');

        $this->assertSame("to_char(created_at, 'YYYY-MM')", $this->monthHarness()->expression('created_at'));
    }

    public function test_delete_from_cloudflare_deletes_file(): void
    {
        Storage::fake('cloudflare');
        Storage::disk('cloudflare')->put('photos/a.jpg', 'x');

        $this->fileHarness()->delete('photos/a.jpg');

        Storage::disk('cloudflare')->assertMissing('photos/a.jpg');
    }

    public function test_delete_from_cloudflare_ignores_null_path(): void
    {
        Storage::fake('cloudflare');

        // Must not throw and must not attempt any deletion.
        $this->fileHarness()->delete(null);

        $this->assertEmpty(Storage::disk('cloudflare')->files('/'));
    }
}
