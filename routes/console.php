<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('stats:warm')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('google:fetch-reviews')->weeklyOn(1, '04:00')->withoutOverlapping();
Schedule::command('seo:warm-for-sale-stats')->cron('*/20 * * * *')->withoutOverlapping();
Schedule::command('sitemap:ping')->dailyAt('05:00')->withoutOverlapping();
Schedule::command('agent:import-testimonials')->weeklyOn(1, '02:00')->withoutOverlapping();
