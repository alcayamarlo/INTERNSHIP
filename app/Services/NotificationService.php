<?php

namespace App\Services;

use App\Jobs\SendNotification;
use App\Mail\SystemNotificationMail;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function send(User $user, string $type, string $title, string $message, array $data = [], bool $queue = true): AppNotification
    {
        if ($queue) {
            SendNotification::dispatch($user->id, $type, $title, $message, $data);

            return new AppNotification([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        }

        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        $this->sendEmail($user, $title, $message, $data);

        return $notification;
    }

    public function sendEmail(User $user, string $title, string $message, array $data = []): void
    {
        if ($user->email_notifications === false) {
            return;
        }

        if (! config('mail.default') || config('mail.default') === 'log') {
            return;
        }

        try {
            Mail::to($user->email)->send(new SystemNotificationMail($title, $message, $data));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function unreadCount(User $user): int
    {
        return $user->appNotifications()->whereNull('read_at')->whereNull('archived_at')->count();
    }
}
