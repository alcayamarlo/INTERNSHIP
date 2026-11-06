<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

/**
 * Pushed to the recipient's private channel when an in-app notification is written.
 *
 * Queued (ShouldBroadcast), so the Pusher HTTP call (0.2-2 s each) never runs inside the
 * click that caused it. With QUEUE_CONNECTION=sync it still goes out immediately, exactly
 * as before; with QUEUE_CONNECTION=database the queue worker sends it about a second later.
 */
class NotificationCreated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    /** A notification deleted before the worker gets to it is simply skipped. */
    public bool $deleteWhenMissingModels = true;

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
