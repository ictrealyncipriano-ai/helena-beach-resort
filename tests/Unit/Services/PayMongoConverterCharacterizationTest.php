<?php

namespace Tests\Unit\Services;

use App\Services\PayMongoService;
use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Money Migration — Phase 7.2 golden characterization for
 * PayMongoService::toCentavos().
 *
 * Pure-unit (no database): captures the legacy float round-trip outputs
 * BEFORE delegating the implementation to Money::toCentavos(). Every
 * absolute golden below was captured from the legacy implementation on
 * this runtime — not inferred.
 *
 * Captured finding: on this runtime the legacy float path agrees with
 * Money::toCentavos() across the whole probed domain, INCLUDING the
 * classic 3-decimal edges (10.005 → 1001, 2.675 → 268). The delegation
 * therefore preserves behavior on the reachable decimal:2 /
 * integer-centavo domain while removing the platform-sensitive binary
 * float path (the 10.005 → 1000 hazard is a known libm-dependent outcome
 * elsewhere). Formatted inputs with grouping/currency symbols
 * (e.g. '₱1,234.56') are outside the reachable caller domain and are
 * intentionally not pinned here.
 */
class PayMongoConverterCharacterizationTest extends TestCase
{
    private PayMongoService $payMongo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payMongo = new PayMongoService();
    }

    /**
     * Reachable 2-decimal domain: legacy outputs are absolute goldens and
     * must remain green through the delegation. Agreement with Money is
     * asserted alongside to lock the delegation target.
     */
    #[DataProvider('twoDecimalDomain')]
    public function test_two_decimal_domain_preserved(mixed $input, int $expected): void
    {
        $this->assertSame($expected, $this->payMongo->toCentavos($input));
        $this->assertSame($expected, Money::toCentavos($input));
    }

    public static function twoDecimalDomain(): array
    {
        return [
            'full total' => ['5000.00', 500000],
            'deposit' => ['1000.00', 100000],
            'balance leg' => ['4000.00', 400000],
            'ledger total' => ['4800.00', 480000],
            'promo total' => ['4500.00', 450000],
            'grouped-rate value' => ['1234.56', 123456],
            'trailing tenth' => ['1500.10', 150010],
            'whole string' => ['1500', 150000],
            'whole int' => [1500, 150000],
            'whole float' => [1500.10, 150010],
            'zero string' => ['0.00', 0],
            'zero digit' => ['0', 0],
            'zero int' => [0, 0],
        ];
    }

    public function test_null_and_empty_yield_zero(): void
    {
        $this->assertSame(0, $this->payMongo->toCentavos(null));
        $this->assertSame(0, $this->payMongo->toCentavos(''));
        $this->assertSame(0, Money::toCentavos(null));
    }

    public function test_sign_and_whitespace_contract(): void
    {
        $this->assertSame(-1001, $this->payMongo->toCentavos('-10.01'));
        $this->assertSame(-1, $this->payMongo->toCentavos('-0.01'));
        $this->assertSame(500, $this->payMongo->toCentavos('+5'));
        $this->assertSame(10000, $this->payMongo->toCentavos('  100.00  '));

        $this->assertSame(-1001, Money::toCentavos('-10.01'));
        $this->assertSame(-1, Money::toCentavos('-0.01'));
        $this->assertSame(500, Money::toCentavos('+5'));
    }

    /**
     * Classic 3-decimal edges, captured (not inferred): the legacy
     * converter yields the half-up outcomes below on this runtime, in
     * agreement with Money. These goldens stay green through delegation.
     */
    #[DataProvider('threeDecimalEdges')]
    public function test_three_decimal_edges_captured(string $input, int $expected): void
    {
        $this->assertSame($expected, $this->payMongo->toCentavos($input));
        $this->assertSame($expected, Money::toCentavos($input));
    }

    public static function threeDecimalEdges(): array
    {
        return [
            'contract anchor' => ['10.005', 1001],
            'classic bankers case' => ['2.675', 268],
            'sub-cent rounds up' => ['0.005', 1],
            'sub-cent rounds down' => ['0.004', 0],
            'carries into pesos' => ['10.015', 1002],
            'small amount' => ['1.005', 101],
            'large fractional' => ['100.055', 10006],
            'half-cent pair one' => ['0.575', 58],
            'half-cent pair two' => ['0.565', 57],
            'just below half' => ['10.004', 1000],
            'just above half' => ['10.006', 1001],
        ];
    }
}
