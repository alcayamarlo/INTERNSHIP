<?php

return [
    // Invitations one employer can send per day (Philippine time).
    'daily_limit' => (int) env('INVITATIONS_DAILY_LIMIT', 20),

    // Days a pending invitation stays open; it also ends when the internship closes.
    'expires_after_days' => (int) env('INVITATIONS_EXPIRE_DAYS', 14),

    // Days before expiry when the student gets a bell reminder.
    'reminder_days_before' => (int) env('INVITATIONS_REMINDER_DAYS', 2),

    'message_max_length' => 500,
];
