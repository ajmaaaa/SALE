<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagePinned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.'.$this->message->room_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'message_pinned',
            'message_id' => $this->message->id,
            'is_pinned' => (bool) $this->message->is_pinned,
            'message' => $this->message->toChatPayload(),
        ];
    }
}
