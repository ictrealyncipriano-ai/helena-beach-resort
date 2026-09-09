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
