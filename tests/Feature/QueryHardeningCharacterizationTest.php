<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use App\Support\SqlLike;
use App\Traits\QueriesByMonth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Slice 9 — query hardening (characterization-first).
 *
 * Desired end state (must FAIL pre-fix unless noted as guard):
 *  - QueriesByMonth::monthExpression() rejects anything outside the
 *    documented expression allow-list instead of interpolating it.
 *  - Admin LIKE searches treat user input literally: % and _ lose their
 *    wildcard meaning (backslash-escaped, per driver LIKE semantics).
 */
class QueryHardeningCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function months(): object
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

    public function test_month_expression_rejects_unknown_column(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->months()->expression('id; DROP TABLE users--');
    }

    public function test_month_expression_rejects_bare_unknown_column(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->months()->expression('created_by');
    }

    public function test_month_expression_accepts_documented_expressions(): void
    {
        foreach ([
            'deposit_paid_at',
            'fully_paid_at',
            'created_at',
            'COALESCE(fully_paid_at, deposit_paid_at)',
        ] as $column) {
            $this->assertStringContainsString($column, $this->months()->expression($column));
        }
    }

    public function test_like_helper_escapes_wildcards(): void
    {
        $this->assertSame('A\\_B', SqlLike::escape('A_B'));
        $this->assertSame('100\\%', SqlLike::escape('100%'));
        $this->assertSame('C:\\\\path', SqlLike::escape('C:\\path'));
        $this->assertSame('plain', SqlLike::escape('plain'));
    }

    private function inquiry(string $name, string $email): Inquiry
    {
        return Inquiry::create([
            'reference_code' => Inquiry::generateReferenceCode(),
            'name' => $name,
            'email' => $email,
            'booking_type' => 'day_tour',
            'status' => 'confirmed',
            'source' => 'website',
            'total_amount' => '1500.00',
            'amount_paid' => '1500.00',
        ]);
    }

    public function test_admin_search_treats_underscore_literally(): void
    {
        $this->inquiry('A_B Guest', 'a-underscore@example.com');
        $this->inquiry('AXB Guest', 'axb-guest@example.com');

        $body = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.inquiries.index', ['search' => 'A_B']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('A_B Guest', $body);
        $this->assertStringNotContainsString('AXB Guest', $body);
    }

    public function test_admin_search_without_wildcards_is_unchanged(): void
    {
        $this->inquiry('AXC Guest', 'axc-guest@example.com');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.inquiries.index', ['search' => 'AX']))
            ->assertOk()
            ->assertSee('AXC Guest');
    }
}
