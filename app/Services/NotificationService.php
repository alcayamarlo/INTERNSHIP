<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Jobs\SendNotification;
use App\Mail\SystemNotificationMail;
use App\Models\AppNotification;
use App\Models\Coordinator;
use App\Models\Student;
use App\Models\User;

class NotificationService
{
    /**
     * The in-app notification is always written immediately so the bell never
     * depends on a queue worker; $queue only controls how the email is sent,
     * and $email: false keeps it to the bell only.
     */
    public function send(User $user, string $type, string $title, string $message, array $data = [], bool $queue = true, bool $email = true): AppNotification
    {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        try {
            broadcast(new NotificationCreated($notification))->toOthers();
        } catch (\Throwable $exception) {
            // Broadcasting is additive; the in-app row above already exists
            // regardless of whether a live push succeeds (e.g. no Pusher keys).
            report($exception);
        }

        if (! $email) {
            return $notification;
        }

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

    /** Notification type for "a student submission is waiting for validation"; opening one goes to the student's record. */
    public const TYPE_VALIDATION_NEEDED = 'validation_needed';

    /**
     * Notifies every coordinator who handles this student (same institution and program).
     * The data always carries student_id, so the notification links to that student's record.
     *
     * @return int how many coordinators were notified
     */
    public function notifyStudentCoordinators(Student $student, string $title, string $message, array $data = []): int
    {
        if (! $student->institution_id) {
            return 0;
        }

        $student->loadMissing('academicProgram');

        return Coordinator::query()
            ->where('institution_id', $student->institution_id)
            ->with('user')
            ->get()
            ->filter(fn (Coordinator $coordinator) => $coordinator->user && $coordinator->canAccessStudent($student))
            ->each(fn (Coordinator $coordinator) => $this->send(
                $coordinator->user,
                self::TYPE_VALIDATION_NEEDED,
                $title,
                $message,
                ['student_id' => $student->id] + $data
            ))
            ->count();
    }

    /** "Juan Dela Cruz (2021-00123)": names the student in coordinator notifications. */
    public static function studentLabel(Student $student): string
    {
        $student->loadMissing('user');
        $name = $student->user?->name ?? 'A student';

        return $student->student_id_number ? $name.' ('.$student->student_id_number.')' : $name;
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
        // Inbox only: archived and deleted (trashed) notifications never count toward the badge.
        return $user->appNotifications()->unread()->count();
    }
}
