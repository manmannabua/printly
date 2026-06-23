<?php

namespace App\Events;

use App\Models\ChatConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Transient typing indicator. Broadcasts immediately (ShouldBroadcastNow) — a
 * queued "is typing" would arrive too late to be useful.
 */
class ChatUserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ChatConversation $conversation,
        public readonly string $side,
        public readonly ?string $userId,
        public readonly ?string $name,
        public readonly bool $isTyping,
    ) {}

    public function broadcastOn(): array
    {
        return $this->conversation->broadcastChannels();
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'side' => $this->side,
            'user_id' => $this->userId,
            'name' => $this->name,
            'is_typing' => $this->isTyping,
        ];
    }
}
