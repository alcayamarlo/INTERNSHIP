<?php

namespace App\Console\Commands;

use App\Services\OjtReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Reminds students who have not logged their time today, 8 hours into the work day (16:00 Manila by default,
 * config('ojt_reminders.daily_log_remind_at')). Runs hourly; before that time it does nothing. Each student gets
 * at most one automatic reminder a day, and none within 8 hours of a coordinator's reminder.
 */
class RemindDailyLogs extends Command
{
    protected $signature = 'ojt:remind-daily-logs {--force : Run now, even before the reminder time}';

    protected $description = 'Remind students who have not logged their OJT time today';

    public function handle(OjtReminderService $reminders): int
    {
        $now = CarbonImmutable::now('Asia/Manila');
        [$hour, $minute] = array_map('intval', explode(':', (string) config('ojt_reminders.daily_log_remind_at', '16:00')) + [1 => 0]);
        if (! $this->option('force') && $now->lt($now->setTime($hour, $minute))) {
            $this->info('Too early: daily time record reminders go out from '.$now->setTime($hour, $minute)->format('g:i A').'.');

            return self::SUCCESS;
        }

        $result = $reminders->remindDailyLogs(null, 'auto');
        $this->info("Reminded {$result['sent']} student(s) to log today's time; {$result['waiting']} skipped (reminded in the last ".OjtReminderService::waitHours().' hours or already today).');

        return self::SUCCESS;
    }
}
