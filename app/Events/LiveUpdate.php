<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * "Something you can see changed" ping, sent to each affected user's private channel.
 *
 * It carries only topic names (e.g. "applications", "placements"), never record data, so it is
 * safe to send to everyone involved. The browser then re-fetches the page it is showing and
 * swaps the matching [data-live-region] areas (resources/js/app.js), which still goes through
 * the normal controllers and policies.
 */
class LiveUpdate implements ShouldBroadcast
{
    use InteractsWithSockets;

    /**
     * @param  list<int>  $userIds  at most 90 (Pusher accepts up to 100 channels per push)
     * @param  list<string>  $topics
     */
    public function __construct(public array $userIds, public array $topics) {}

    public function broadcastOn(): array
    {
        return array_map(fn (int $id) => new PrivateChannel('App.Models.User.'.$id), $this->userIds);
    }

    public function broadcastAs(): string
    {
        return 'live.update';
    }

    public function broadcastWith(): array
    {
        return ['topics' => $this->topics];
    }
}
