<?php

use App\Jobs\DetectCriticalStocksJob;
use App\Jobs\DetectOverdueHealthEventsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new DetectCriticalStocksJob)->dailyAt('06:00');
Schedule::job(new DetectOverdueHealthEventsJob)->dailyAt('06:30');
