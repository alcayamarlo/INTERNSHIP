<?php

return [
    // Weekly reports are only tracked for weeks ending on or after this date, so the first run after
    // the feature went live does not flag every past week of older placements.
    'weekly_reports_from' => env('OJT_REMINDERS_WEEKLY_FROM', '2026-11-17'),

    // Days before the due date when the "due soon" reminder goes out.
    'weekly_report_days_before' => 1,
    'evaluation_days_before' => 2,
];
