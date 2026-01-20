<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupCursorMoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $x;
    public $y;
    public $username;
    public $groupId;
    /**
     * Create a new event instance.
     */
    public function __construct($x, $y, $username, $groupId)
    {
        $this->x = $x;
        $this->y = $y;
        $this->username = $username;
        $this->groupId = $groupId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('group.' . $this->groupId)];
    }

    public function broadcastAs(): string
    {
        return 'GroupCursorMoved';
    }
}
