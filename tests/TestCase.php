<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pin "now" before the earliest hardcoded fixture date (2026-09-01)
     * so date validation (check_in after_or_equal:today) stays green
     * regardless of the real calendar date. Re-asserted every test so
     * no travel() leak can cross test boundaries.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
