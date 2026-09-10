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
