<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire pending reservations past their 48h hold window and release date blocks.
// Vercel Hobby plans only allow daily cron jobs, so run once per day at 02:00 UTC.
Schedule::command('reservations:release-expired --hours=48')->dailyAt('02:00');
