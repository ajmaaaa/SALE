<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $messageData;

    public function __construct(public Message $message, ?array $payload = null)
    {
        $this->messageData = $payload ?? $message->toChatPayload();
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
            'type' => 'new_message',
            'message' => $this->messageData,
        ];
    }
}
