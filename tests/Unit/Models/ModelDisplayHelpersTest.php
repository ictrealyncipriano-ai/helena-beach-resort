<?php

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9.7 — direct coverage for model methods that were previously only
 * exercised indirectly (or never): ActivityLog label helpers and the
 * PromoCode display helpers.
 */
class ModelDisplayHelpersTest extends TestCase
{
    use RefreshDatabase;

    private function log(string $action, ?string $userName = null): ActivityLog
    {
        return ActivityLog::create([
            'action' => $action,
            'user_name' => $userName,
        ]);
    }

    public function test_action_label_maps_known_actions(): void
    {
        $this->assertSame('Booking confirmed', $this->log('inquiry.confirmed')->actionLabel());
        $this->assertSame('Payment refunded', $this->log('inquiry.refunded')->actionLabel());
        $this->assertSame('Guest submitted a review', $this->log('guest.reviewed')->actionLabel());
        $this->assertSame('All FAQs activated', $this->log('faq.activated')->actionLabel());
    }

    public function test_action_label_falls_back_for_unknown_action(): void
    {
        $this->assertSame('Foo bar baz', $this->log('foo.bar.baz')->actionLabel());
        $this->assertSame('Unknown', $this->log('unknown')->actionLabel());
    }

    public function test_actor_label_returns_snapshot_name(): void
    {
        $this->assertSame('Alice', $this->log('inquiry.created', 'Alice')->actorLabel());
    }

    public function test_actor_label_falls_back_to_guest(): void
    {
        $this->assertSame('Guest', $this->log('guest.cancelled', null)->actorLabel());
    }

    public function test_normalize_uppercases_and_trims_code(): void
    {
        $this->assertSame('HELLO10', PromoCode::normalize('  hello10  '));
    }

    public function test_is_percent_distinguishes_types(): void
    {
        $this->assertTrue(PromoCode::create(['code' => 'P', 'type' => 'percent', 'value' => 10])->isPercent());
        $this->assertFalse(PromoCode::create(['code' => 'F', 'type' => 'fixed', 'value' => 500])->isPercent());
    }

    public function test_value_label_percent_and_fixed(): void
    {
        $this->assertSame('10%', PromoCode::create(['code' => 'P', 'type' => 'percent', 'value' => 10])->valueLabel());
        $this->assertSame('₱500.00', PromoCode::create(['code' => 'F', 'type' => 'fixed', 'value' => 500])->valueLabel());
    }

    public function test_has_reached_usage_limit(): void
    {
        $limited = PromoCode::create(['code' => 'L', 'type' => 'percent', 'value' => 10, 'usage_limit' => 5, 'used_count' => 5]);
        $this->assertTrue($limited->hasReachedUsageLimit());

        $under = PromoCode::create(['code' => 'U', 'type' => 'percent', 'value' => 10, 'usage_limit' => 5, 'used_count' => 4]);
        $this->assertFalse($under->hasReachedUsageLimit());

        $unlimited = PromoCode::create(['code' => 'INF', 'type' => 'percent', 'value' => 10, 'usage_limit' => null]);
        $this->assertFalse($unlimited->hasReachedUsageLimit());
    }
}
