<?php

namespace App\Services;

use App\Events\ChatMessageEvent;
use App\Events\ChatMessageRead;
use App\Events\ChatReactionToggled;
use App\Events\ChatUserTyping;
use App\Models\ChatConversation;
use App\Models\ChatConversationParticipant;
use App\Models\ChatMessage;
use App\Models\ChatMessageReaction;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Owns chat conversations and messages for the two relationships Printly needs:
 * Admin↔Store (internal, store-scoped) and Store↔Customer (per order, with a
 * guest participant). Visibility is role/store based; participant rows only track
 * per-user read state.
 */
class ChatService
{
    /** Find or create the single internal thread for a store. */
    public function ensureInternalConversation(Store $store): ChatConversation
    {
        return ChatConversation::firstOrCreate(
            ['type' => ChatConversation::TYPE_INTERNAL, 'store_id' => $store->id],
            ['title' => $store->name, 'last_message_at' => now()],
        );
    }

    /** Find or create the per-order thread (store side + guest customer). */
    public function ensureOrderConversation(Order $order): ChatConversation
    {
        $conversation = ChatConversation::firstOrCreate(
            ['type' => ChatConversation::TYPE_ORDER, 'order_id' => $order->id],
            ['store_id' => $order->store_id, 'title' => "Order {$order->code}", 'last_message_at' => now()],
        );

        // One persistent customer participant row (no user) for read tracking.
        if ($conversation->wasRecentlyCreated) {
            $conversation->participants()->create([
                'side' => ChatConversationParticipant::SIDE_CUSTOMER,
                'user_id' => null,
            ]);
        }

        return $conversation;
    }

    /** Whether a logged-in user may see/post in a conversation. */
    public function userCanAccess(User $user, ChatConversation $conversation): bool
    {
        return $user->is_admin || ($conversation->store_id && $user->belongsToStore($conversation->store_id));
    }

    /**
     * Conversations visible to a user, newest activity first, with unread counts.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function conversationsForUser(User $user): Collection
    {
        $query = ChatConversation::query()->with(['store', 'order']);

        if (! $user->is_admin) {
            $storeIds = $user->stores()->pluck('stores.id')->all();
            $query->whereIn('store_id', $storeIds);
        }

        // Internal threads are always listed; order threads only once they have activity.
        $query->where(fn ($q) => $q
            ->where('type', ChatConversation::TYPE_INTERNAL)
            ->orWhereNotNull('last_message_at'));

        return $query->orderByDesc('last_message_at')->get()
            ->map(fn (ChatConversation $c) => $this->presentConversation($c, $user));
    }

    /**
     * @return array<string, mixed>
     */
    public function presentConversation(ChatConversation $conversation, ?User $viewer): array
    {
        $side = $this->sideForUser($conversation, $viewer);
        $last = $conversation->messages()->latest()->first();

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'store_id' => $conversation->store_id,
            'order_id' => $conversation->order_id,
            'order_code' => $conversation->relationLoaded('order') ? $conversation->order?->code : null,
            'title' => $this->titleFor($conversation, $side),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'last_message_preview' => $last && ! $last->deleted_at ? Str::limit((string) $last->body, 60) : null,
            'unread_count' => $viewer ? $this->unreadCount($conversation, $side, (string) $viewer->id) : 0,
            'my_side' => $side,
        ];
    }

    /** Persist + broadcast a new message. */
    public function sendMessage(
        ChatConversation $conversation,
        string $side,
        ?string $userId,
        string $body,
        ?string $replyToId = null,
    ): ChatMessage {
        $message = $conversation->messages()->create([
            'sender_user_id' => $userId,
            'sender_side' => $side,
            'body' => $body,
            'type' => 'text',
            'reply_to_id' => $replyToId,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $message->load('sender', 'reactions');
        ChatMessageEvent::dispatch($conversation, $message->toChatArray(), 'sent');

        return $message;
    }

    public function editMessage(ChatMessage $message, string $body): ChatMessage
    {
        $message->update(['body' => $body, 'edited_at' => now()]);
        $message->load('sender', 'reactions');
        ChatMessageEvent::dispatch($message->conversation, $message->toChatArray(), 'edited');

        return $message;
    }

    public function deleteMessage(ChatMessage $message): void
    {
        $message->update(['deleted_at' => now()]);
        $message->load('sender', 'reactions');
        ChatMessageEvent::dispatch($message->conversation, $message->toChatArray(), 'deleted');
    }

    /** Toggle a reaction (per user, or once for the guest customer). */
    public function toggleReaction(ChatMessage $message, ?string $userId, string $emoji): void
    {
        $existing = ChatMessageReaction::where('message_id', $message->id)
            ->where('emoji', $emoji)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->when($userId === null, fn ($q) => $q->whereNull('user_id'))
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            ChatMessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $userId,
                'emoji' => $emoji,
            ]);
        }

        $message->load('reactions');
        ChatReactionToggled::dispatch($message->conversation, $message->id, $message->toChatArray()['reactions']);
    }

    /** Broadcast a transient typing indicator. */
    public function emitTyping(ChatConversation $conversation, string $side, ?string $userId, ?string $name, bool $isTyping): void
    {
        ChatUserTyping::dispatch($conversation, $side, $userId, $name, $isTyping);
    }

    /** Record a read up to a message + broadcast a receipt. */
    public function markRead(ChatConversation $conversation, string $side, ?string $userId, string $messageId): void
    {
        $participant = $conversation->participants()->updateOrCreate(
            ['side' => $side, 'user_id' => $userId],
            ['last_read_message_id' => $messageId],
        );

        ChatMessageRead::dispatch($conversation, $side, $userId, $messageId);

        unset($participant);
    }

    private function sideForUser(ChatConversation $conversation, ?User $user): string
    {
        if (! $user) {
            return ChatConversationParticipant::SIDE_CUSTOMER;
        }

        return $user->is_admin
            ? ChatConversationParticipant::SIDE_ADMIN
            : ChatConversationParticipant::SIDE_STORE;
    }

    private function titleFor(ChatConversation $conversation, string $viewerSide): string
    {
        if ($conversation->type === ChatConversation::TYPE_ORDER) {
            return $conversation->title ?? 'Order';
        }

        // Internal: the store user sees "Printly Admin"; the admin sees the store.
        return $viewerSide === ChatConversationParticipant::SIDE_STORE
            ? 'Printly Admin'
            : ($conversation->title ?? ($conversation->store?->name ?? 'Store'));
    }

    private function unreadCount(ChatConversation $conversation, string $side, ?string $userId): int
    {
        $participant = $conversation->participants()
            ->where('side', $side)
            ->where('user_id', $userId)
            ->first();

        $query = $conversation->messages()
            ->whereNull('deleted_at')
            ->where('sender_side', '!=', $side);

        if ($participant?->last_read_message_id) {
            $readAt = ChatMessage::where('id', $participant->last_read_message_id)->value('created_at');
            if ($readAt) {
                $query->where('created_at', '>', $readAt);
            }
        }

        return $query->count();
    }
}
