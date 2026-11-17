<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Pushed to the recipient's private channel when an in-app notification is written.
 *
 * Sent straight to Pusher (ShouldBroadcastNow), so it is real time without a queue worker.
 * NotificationService sends it with defer(): the Pusher HTTP call (0.2-2 s) runs right after
 * the response has gone to the browser, so the click that caused it never waits on Pusher.
 */
class NotificationCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(public AppNotification $notification) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->notification->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'created_at' => $this->notification->created_at->toIso8601String(),
            'show_url' => route('notifications.show', $this->notification),
            'read_url' => route('notifications.read', $this->notification),
            // User id of the company whose chat just opened, so an open chat page can unlock live.
            'messaging_open_with' => $this->notification->data['messaging_open_with'] ?? null,
        ];
    }
}
