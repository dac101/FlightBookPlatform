<?php

use App\Console\Commands\FetchFlightDataCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Fetch live arrival data from AviationStack once per day at 06:00.
 *
 * Each run uses 8 API requests (one per airport).
 * Free-tier limit: 100 requests/month ≈ 3 full runs/month at daily frequency.
 */
Schedule::command(FetchFlightDataCommand::class)
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/flights-fetch.log'));

Schedule::command('telescope:prune --hours=48')->hourly();
