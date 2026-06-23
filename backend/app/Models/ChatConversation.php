<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    use HasUuid;

    public const TYPE_INTERNAL = 'internal';
    public const TYPE_ORDER = 'order';

    protected $fillable = [
        'type',
        'store_id',
        'order_id',
        'title',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(ChatConversationParticipant::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Channels a chat event for this conversation broadcasts on. Internal threads
     * use only the private participant channel; order threads also broadcast to a
     * public, code-scoped channel so the guest customer (no login) receives events.
     *
     * @return array<int, Channel|PrivateChannel>
     */
    public function broadcastChannels(): array
    {
        $channels = [new PrivateChannel("chat.conversation.{$this->id}")];

        if ($this->type === self::TYPE_ORDER) {
            $code = $this->relationLoaded('order') ? $this->order?->code : $this->order()->value('code');
            if ($code) {
                $channels[] = new Channel("chat.order.{$code}");
            }
        }

        return $channels;
    }
}
