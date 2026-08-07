<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire pending reservations past their 48h hold window and release date blocks.
// The deploy triggers this via the HTTP cron endpoint (POST /cron/reservations,
// see routes/web.php) on Vercel/Render, so the scheduler entry is intentionally
// disabled here to avoid running the job twice.
// Schedule::command('reservations:release-expired --hours=48')->dailyAt('02:00');
