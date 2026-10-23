<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mijoz rasmlarini TTL bo'yicha o'chirish (uploads 24 soat, results 7 kun) va osilib qolgan so'rovlarni yopish.
// Lokalda ishga tushirish: sail artisan schedule:work
Schedule::command('try-on:prune')->hourly()->withoutOverlapping();
