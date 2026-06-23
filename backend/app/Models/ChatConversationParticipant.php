<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatConversationParticipant extends Model
{
    use HasUuid;

    public const SIDE_ADMIN = 'admin';
    public const SIDE_STORE = 'store';
    public const SIDE_CUSTOMER = 'customer';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'side',
        'last_read_message_id',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
