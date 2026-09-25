<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $roomId;
    public int $messageId;

    public function __construct(Message $message)
    {
        $this->roomId = $message->room_id;
        $this->messageId = $message->id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.'.$this->roomId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'message_deleted',
            'message_id' => $this->messageId,
            'room_id' => $this->roomId,
        ];
    }
}
