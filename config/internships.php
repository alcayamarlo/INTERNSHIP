<?php

return [
    /*
    | Job alerts: when a posting goes live, students whose competency match is at least
    | this percentage are notified. Postings with no listed requirements alert every
    | eligible student in the posting's programs.
    */
    'alert_min_match' => (int) env('INTERNSHIP_ALERT_MIN_MATCH', 50),

    /*
    | Deadline reminders: how many days before an internship's application deadline
    | the "closing soon" reminder is sent (once per deadline).
    */
    'deadline_reminder_days' => (int) env('INTERNSHIP_DEADLINE_REMINDER_DAYS', 3),
];
