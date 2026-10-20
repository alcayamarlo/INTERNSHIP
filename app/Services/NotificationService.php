<?php

namespace App\Services;

use App\Jobs\SendNotification;
use App\Mail\SystemNotificationMail;
use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    /**
     * The in-app notification is always written immediately so the bell never
     * depends on a queue worker; $queue only controls how the email is sent.
     */
    public function send(User $user, string $type, string $title, string $message, array $data = [], bool $queue = true): AppNotification
    {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        if ($queue) {
            try {
                SendNotification::dispatch($user->id, $type, $title, $message, $data, $notification->id);
            } catch (\Throwable $exception) {
                report($exception);
            }

            return $notification;
        }

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

        app(MailSender::class)->send($user->email, new SystemNotificationMail($title, $message, $data), 'notification');
    }

    public function unreadCount(User $user): int
    {
        return $user->appNotifications()->whereNull('read_at')->whereNull('archived_at')->count();
    }
}
