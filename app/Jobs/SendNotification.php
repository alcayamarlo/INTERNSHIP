<?php

namespace App\Jobs;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends the email copy of an in-app notification. The in-app row is written
 * synchronously by NotificationService, so the bell works even when no queue
 * worker is running. Jobs queued before that change carry no notificationId
 * and still create the row here.
 */
class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public string $message,
        public array $data = [],
        public ?int $notificationId = null,
    ) {}

    public function handle(\App\Services\NotificationService $notificationService): void
    {
        $user = User::findOrFail($this->userId);

        if ($this->notificationId === null) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => $this->type,
                'title' => $this->title,
                'message' => $this->message,
                'data' => $this->data,
            ]);
        }

        $notificationService->sendEmail(
            $user,
            $this->title,
            $this->message,
            $this->data
        );

        Log::info("Notification sent to user {$this->userId}: {$this->title}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to send notification to user {$this->userId}: {$exception->getMessage()}");
    }
}
