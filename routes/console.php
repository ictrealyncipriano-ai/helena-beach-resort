<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire pending reservations past their hold window (booking_hold_hours
// setting, 48h default) and release date blocks.
// The scheduler is the primary trigger. The HTTP endpoint (POST /cron/reservations,
// see routes/web.php) remains only as a manual fallback for hosts without scheduler.
Schedule::command('reservations:release-expired')->dailyAt('02:00');

// WP-4: conservative payment reconciliation (stale-pending sweep +
// non-confirmed-with-session re-check). Cheap when idle (one indexed query,
// zero API calls unless a stale pending exists); tune the cadence after
// observing PayMongo API usage in production.
Schedule::command('payments:reconcile')->everyTenMinutes();

// WP-7: retry failed online refunds with backoff (inquiry queue + late
// ledger queue). Rows at MAX_ATTEMPTS are only reported for human review.
Schedule::command('payments:retry-refunds')->everyThirtyMinutes();
