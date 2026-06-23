<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    use HasUuid;

    protected $fillable = [
        'conversation_id',
        'sender_user_id',
        'sender_side',
        'body',
        'type',
        'reply_to_id',
        'edited_at',
        'deleted_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ChatMessageReaction::class, 'message_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ChatMessageAttachment::class, 'message_id');
    }

    /**
     * Serialise for API responses and broadcast payloads. Deleted messages keep
     * their envelope (so the thread shows "message deleted") but drop the body.
     *
     * @return array<string, mixed>
     */
    public function toChatArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_user_id' => $this->sender_user_id,
            'sender_side' => $this->sender_side,
            'sender_name' => $this->relationLoaded('sender') ? ($this->sender?->name ?? $this->sender?->email) : null,
            'body' => $this->deleted_at ? null : $this->body,
            'type' => $this->type,
            'reply_to_id' => $this->reply_to_id,
            'is_deleted' => $this->deleted_at !== null,
            'edited_at' => $this->edited_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'reactions' => $this->relationLoaded('reactions')
                ? $this->reactions
                    ->groupBy('emoji')
                    ->map(fn ($group, $emoji) => [
                        'emoji' => $emoji,
                        'count' => $group->count(),
                        'user_ids' => $group->pluck('user_id')->filter()->values(),
                    ])
                    ->values()
                : [],
            'attachments' => $this->relationLoaded('attachments')
                ? $this->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'mime' => $a->mime,
                    'size' => $a->size,
                    'url' => url("/api/v1/chat-files/{$a->id}"),
                ])->values()
                : [],
        ];
    }
}

