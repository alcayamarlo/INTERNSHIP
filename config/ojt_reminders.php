<?php

return [
    // Weekly reports are only tracked for weeks ending on or after this date, so the first run after
    // the feature went live does not flag every past week of older placements.
    'weekly_reports_from' => env('OJT_REMINDERS_WEEKLY_FROM', '2026-11-17'),

    // Days before the due date when the "due soon" reminder goes out.
    'weekly_report_days_before' => 1,
    'evaluation_days_before' => 2,

    // Daily time record: the automatic reminder goes out at this time (Manila) to students with no time logged
    // today, 8 hours into a 08:00 work day. A student is never reminded about it again within the wait.
    'daily_log_remind_at' => env('OJT_DAILY_LOG_REMIND_AT', '16:00'),
    'daily_log_wait_hours' => 8,
];
