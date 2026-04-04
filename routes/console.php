<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Alertes automatiques de notification ───
Schedule::command('notifications:send-alerts --type=reservations')->everyFifteenMinutes();
Schedule::command('notifications:send-alerts --type=insurances --days=30')->dailyAt('08:00');
Schedule::command('notifications:send-alerts --type=inspections --days=30')->dailyAt('08:00');
Schedule::command('notifications:send-alerts --type=vignettes --days=30')->dailyAt('08:00');
Schedule::command('notifications:send-alerts --type=maintenances')->dailyAt('08:00');
Schedule::command('notifications:send-alerts --type=billing')->dailyAt('09:00');
Schedule::command('notifications:send-alerts --type=clients --days=60')->weeklyOn(1, '08:00');
