<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function send(User $user, string $type, string $title, string $message, array $data = []): AppNotification
    {
        return AppNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function unreadCount(User $user): int
    {
        return $user->appNotifications()->whereNull('read_at')->count();
    }
}
