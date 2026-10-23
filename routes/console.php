<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Delete customer images after their TTL (uploads 24 h, results 7 days) and close stuck requests.
// Locally: sail artisan schedule:work. In production the platform runs `schedule:run` every minute.
Schedule::command('try-on:prune')->hourly()->withoutOverlapping();
