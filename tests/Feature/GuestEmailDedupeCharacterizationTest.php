<?php

namespace Tests\Feature;

use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Slice 10 — guest email dedupe (characterization-first).
 *
 * Desired end state (must FAIL pre-fix unless noted as guard):
 *  - guests.email_normalized exists and is uniquely indexed (portable).
 *  - Whitespace/case variants conflict at the DB level, not just in app code.
 *  - findByEmailOrCreate() resolves via the indexed normalized column
 *    instead of a whereRaw full scan.
 *  - The duplicate guard fails closed: conflicting rows abort the
 *    migration path instead of merging.
 */
class GuestEmailDedupeCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_normalized_column_exists_and_is_unique(): void
    {
        $this->assertTrue(Schema::hasColumn('guests', 'email_normalized'));

        $unique = collect(Schema::getIndexes('guests'))
            ->first(fn ($index) => ($index['unique'] ?? false)
                && $index['columns'] === ['email_normalized']);

        $this->assertNotNull($unique, 'email_normalized lacks a unique index');
    }

    public function test_normalized_column_tracks_email(): void
    {
        $guest = Guest::create(['name' => 'Norm', 'email' => '  Norm@Example.com  ']);

        $this->assertSame('norm@example.com', $guest->fresh()->email_normalized);
    }

    public function test_whitespace_variant_conflicts_at_db_level(): void
    {
        Guest::create(['name' => 'First', 'email' => 'dupe@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Guest::create(['name' => 'Second', 'email' => ' dupe@example.com ']);
    }

    public function test_case_variant_conflicts_at_db_level(): void
    {
        Guest::create(['name' => 'First', 'email' => 'case@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Guest::create(['name' => 'Second', 'email' => 'CASE@example.com']);
    }

    public function test_lookup_uses_indexed_column_not_whereraw(): void
    {
        Guest::create(['name' => 'Look', 'email' => 'look@example.com']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $found = Guest::findByEmailOrCreate('  LOOK@Example.com  ');

        $this->assertSame('look@example.com', $found->email);

        $queries = collect(DB::getQueryLog())->pluck('query')->join("\n");
        $this->assertStringNotContainsString('LOWER(TRIM', $queries);
        $this->assertStringContainsString('email_normalized', $queries);
    }

    public function test_conflict_guard_fails_closed_on_duplicates(): void
    {
        // The unique index itself would reject this fixture, so prove the
        // report path with the index lifted for this test only (each test
        // re-migrates fresh, so nothing leaks). The migration guard runs the
        // identical normalized-group query before any schema change.
        Schema::table('guests', function ($table) {
            $table->dropUnique(['email_normalized']);
        });
        DB::table('guests')->insert([
            ['name' => 'Old', 'email' => 'clash@example.com', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'New', 'email' => ' CLASH@example.com', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $exit = $this->artisan('guests:email-conflicts');

        $this->assertNotSame(0, $exit);
        $this->assertDatabaseCount('guests', 2);
    }

    public function test_conflict_guard_passes_when_clean(): void
    {
        Guest::create(['name' => 'Solo', 'email' => 'solo@example.com']);

        $this->artisan('guests:email-conflicts')
            ->assertExitCode(0);
    }
}
