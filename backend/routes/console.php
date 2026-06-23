<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Privacy: purge uploaded print files a week after an order is terminal.
Schedule::command('orders:purge-files')->dailyAt('03:00');
