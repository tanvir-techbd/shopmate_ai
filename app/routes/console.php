<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires `php artisan schedule:work` (or a system cron calling
// `php artisan schedule:run` every minute) to actually fire - see README.
Schedule::command('alerts:check')->everyFiveMinutes();
