<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Autopilot schedule (driven by a single Hostinger cron: schedule:run)
|--------------------------------------------------------------------------
*/

// Generate the day's drafts for autopilot domains (early morning, Oslo time).
Schedule::command('seo:generate-daily')
    ->dailyAt('05:00')
    ->timezone('Europe/Oslo')
    ->withoutOverlapping();

// Publish any due, scheduled articles.
Schedule::command('seo:publish-scheduled')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Drain the database queue (only relevant if QUEUE_CONNECTION=database).
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=2')
    ->everyMinute()
    ->withoutOverlapping();

// Existing feature: daily ranking check (kept).
Schedule::job(new \App\Jobs\CheckAllRankings())
    ->dailyAt('03:00')
    ->withoutOverlapping();
