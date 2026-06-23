<?php

namespace App\Events;

use App\Models\ChatConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A chat message was created, edited, or deleted. One event covers the message
 * lifecycle (frontend switches on `kind`) to keep the channel surface small.
 */
class ChatMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $message
     * @param  'sent'|'edited'|'deleted'  $kind
     */
    public function __construct(
        public readonly ChatConversation $conversation,
        public readonly array $message,
        public readonly string $kind,
    ) {}

    public function broadcastOn(): array
    {
        return $this->conversation->broadcastChannels();
    }

    public function broadcastAs(): string
    {
        return 'message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['kind' => $this->kind, 'message' => $this->message];
    }
}
