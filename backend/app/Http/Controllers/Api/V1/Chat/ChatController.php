<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Store;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Internal Admin↔Store chat (authenticated). Visibility is role/store based:
 * admins see every conversation, store members see their stores'.
 */
class ChatController extends BaseController
{
    public function __construct(private readonly ChatService $chat) {}

    /** GET /chat/conversations */
    public function index(Request $request): JsonResponse
    {
        return $this->success($this->chat->conversationsForUser($request->user()));
    }

    /** POST /chat/conversations — start (or reuse) the internal thread for a store. */
    public function startInternal(Request $request): JsonResponse
    {
        $data = $request->validate(['store_id' => ['required', 'string', 'exists:stores,id']]);
        $store = Store::findOrFail($data['store_id']);

        if (! $this->chat->userCanAccess($request->user(), $this->conversationForStore($store))) {
            return $this->error('You do not have access to this store.', 403);
        }

        $conversation = $this->chat->ensureInternalConversation($store)->load('store', 'order');

        return $this->success($this->chat->presentConversation($conversation, $request->user()));
    }

    /** GET /chat/conversations/{id}/messages */
    public function messages(Request $request, string $id): JsonResponse
    {
        $conversation = ChatConversation::findOrFail($id);
        if (! $this->chat->userCanAccess($request->user(), $conversation)) {
            return $this->error('Forbidden.', 403);
        }

        return response()->json($this->paginateMessages($conversation, $request));
    }

    /** POST /chat/conversations/{id}/messages */
    public function send(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'reply_to_id' => ['nullable', 'string'],
        ]);

        $conversation = ChatConversation::findOrFail($id);
        $user = $request->user();
        if (! $this->chat->userCanAccess($user, $conversation)) {
            return $this->error('Forbidden.', 403);
        }

        $side = $user->is_admin ? 'admin' : 'store';
        $message = $this->chat->sendMessage($conversation, $side, (string) $user->id, $data['body'], $data['reply_to_id'] ?? null);

        return $this->success($message->toChatArray(), 'Sent.');
    }

    /** POST /chat/conversations/{id}/read */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['message_id' => ['required', 'string']]);
        $conversation = ChatConversation::findOrFail($id);
        $user = $request->user();
        if (! $this->chat->userCanAccess($user, $conversation)) {
            return $this->error('Forbidden.', 403);
        }

        $side = $user->is_admin ? 'admin' : 'store';
        $this->chat->markRead($conversation, $side, (string) $user->id, $data['message_id']);

        return $this->success(null, 'Read.');
    }

    /** POST /chat/conversations/{id}/typing */
    public function typing(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['is_typing' => ['required', 'boolean']]);
        $conversation = ChatConversation::findOrFail($id);
        $user = $request->user();
        if (! $this->chat->userCanAccess($user, $conversation)) {
            return $this->error('Forbidden.', 403);
        }

        $side = $user->is_admin ? 'admin' : 'store';
        $this->chat->emitTyping($conversation, $side, (string) $user->id, $user->name ?? $user->email, (bool) $data['is_typing']);

        return $this->success(null);
    }

    /** PATCH /chat/messages/{id} */
    public function edit(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $message = ChatMessage::findOrFail($id);
        if ($message->sender_user_id !== $request->user()->id) {
            return $this->error('You can only edit your own messages.', 403);
        }

        return $this->success($this->chat->editMessage($message, $data['body'])->toChatArray(), 'Updated.');
    }

    /** DELETE /chat/messages/{id} */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $message = ChatMessage::findOrFail($id);
        $user = $request->user();
        if ($message->sender_user_id !== $user->id && ! $user->is_admin) {
            return $this->error('You can only delete your own messages.', 403);
        }

        $this->chat->deleteMessage($message);

        return $this->success(null, 'Deleted.');
    }

    /** POST /chat/messages/{id}/reactions */
    public function react(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['emoji' => ['required', 'string', 'max:16']]);
        $message = ChatMessage::with('conversation')->findOrFail($id);
        $user = $request->user();
        if (! $this->chat->userCanAccess($user, $message->conversation)) {
            return $this->error('Forbidden.', 403);
        }

        $this->chat->toggleReaction($message, (string) $user->id, $data['emoji']);

        return $this->success(null, 'Reaction updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function paginateMessages(ChatConversation $conversation, Request $request): array
    {
        $limit = (int) $request->input('limit', 50);
        $query = $conversation->messages()->with('sender', 'reactions')->latest();

        if ($beforeId = $request->input('before_id')) {
            $before = ChatMessage::find($beforeId);
            if ($before) {
                $query->where('created_at', '<', $before->created_at);
            }
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;

        return [
            'data' => $rows->take($limit)->reverse()->values()->map(fn (ChatMessage $m) => $m->toChatArray()),
            'meta' => ['has_more' => $hasMore],
        ];
    }

    private function conversationForStore(Store $store): ChatConversation
    {
        $c = new ChatConversation();
        $c->store_id = $store->id;
        $c->type = ChatConversation::TYPE_INTERNAL;

        return $c;
    }
}
