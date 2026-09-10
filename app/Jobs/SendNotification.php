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
        public array $data = []
    ) {}

    public function handle(): void
    {
        $user = User::findOrFail($this->userId);

        AppNotification::create([
            'user_id' => $user->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
        ]);

        Log::info("Notification sent to user {$this->userId}: {$this->title}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to send notification to user {$this->userId}: {$exception->getMessage()}");
    }
}
