<?php

use App\Jobs\CheckCompetencyExpiry;
use App\Jobs\ScheduledBackup;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckCompetencyExpiry)->dailyAt('08:00');
Schedule::job(new ScheduledBackup)->dailyAt('02:00');
Schedule::command('cache:prune-stale-tags')->hourly();
Schedule::command('invitations:expire')->hourly()->withoutOverlapping();
Schedule::command('moa:remind')->dailyAt('07:00');
Schedule::command('internships:remind-deadlines')->dailyAt('08:30')->withoutOverlapping();
Schedule::command('logs:prune')->dailyAt('03:00');
// Heartbeat for the admin health page ("scheduler last ran").
Schedule::call(fn () => cache()->forever('scheduler:last_run', now()->toIso8601String()))->everyMinute()->name('scheduler-heartbeat');
