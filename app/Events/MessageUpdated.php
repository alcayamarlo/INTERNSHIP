<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Pushed to the other person in a chat when a message there changes (reaction, pin, edit,
 * unsend), so their open conversation re-renders that one message.
 */
class MessageUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    /** @param  int  $actorId  the person who made the change (the open thread on the other side) */
    public function __construct(public Message $message, public int $actorId) {}

    public function broadcastOn(): array
    {
        $otherId = $this->message->sender_id === $this->actorId ? $this->message->receiver_id : $this->message->sender_id;

        return [new PrivateChannel('App.Models.User.'.$otherId)];
    }

    public function broadcastAs(): string
    {
        return 'message.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'thread_user_id' => $this->actorId,
            'row_url' => route('messages.row', $this->message),
        ];
    }
}
