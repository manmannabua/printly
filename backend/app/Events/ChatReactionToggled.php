<?php

namespace App\Events;

use App\Models\ChatConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A reaction was added or removed on a message. Broadcasts the message's full
 * reaction summary so every client converges on the same state.
 */
class ChatReactionToggled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, mixed>  $reactions
     */
    public function __construct(
        public readonly ChatConversation $conversation,
        public readonly string $messageId,
        public readonly array $reactions,
    ) {}

    public function broadcastOn(): array
    {
        return $this->conversation->broadcastChannels();
    }

    public function broadcastAs(): string
    {
        return 'reaction.toggled';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['message_id' => $this->messageId, 'reactions' => $this->reactions];
    }
}
