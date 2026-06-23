<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:monitor-late')->everyMinute();
Schedule::command('leave:accrue-daily')->dailyAt('00:05');
Schedule::command('leave:grant-sil')->dailyAt('00:10');
Schedule::command('leave:convert-sil-to-cash')->yearlyOn(12, 31, '23:00');
Schedule::command('leave:forfeit-expired-carryover')->dailyAt('00:15');
Schedule::command('attendance:credit-daily')->dailyAt('00:25');
Schedule::command('leave:reset-annual')->yearlyOn(1, 1, '00:30');
Schedule::command('leave:cleanup-accrual-runs')->dailyAt('02:30');
Schedule::command('import:cleanup')->dailyAt('03:00');
Schedule::command('it:check-sla-breach')->everyFifteenMinutes();
Schedule::command('it:auto-close-tickets')->hourly();
Schedule::command('training:check-expiry')->dailyAt('07:00');
Schedule::command('training:mandatory-check')->weeklyOn(1, '08:00');
Schedule::command('exams:expire-attempts')->everyFiveMinutes();
Schedule::command('benefits:check-expiry')->dailyAt('07:30');
Schedule::command('benefits:eligibility-check')->monthlyOn(1, '08:00');
Schedule::command('signature:expire-stale')->dailyAt('01:00');
Schedule::command('signature:send-reminders')->dailyAt('09:00');
Schedule::command('chat:purge-expired')->dailyAt('03:30');
Schedule::command('chat:end-stuck-calls')->everyFiveMinutes();
Schedule::command('db:backup --compress --upload')->dailyAt('01:30');
Schedule::command('timekeeping:cleanup-photos')->dailyAt('02:00');
Schedule::command('parse-sessions:mark-abandoned')->dailyAt('04:00');
// sanctum:prune-expired removed — SPA cookie auth only; personal_access_tokens table not used
