<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public per-order chat for the guest customer. The order code is the bearer
 * (same access model as the order-status page) — no login required.
 */
class OrderChatController extends BaseController
{
    public function __construct(private readonly ChatService $chat) {}

    /** GET /orders/{code}/chat — the thread + recent messages. */
    public function thread(string $code): JsonResponse
    {
        $order = $this->resolveOrder($code);
        $conversation = $this->chat->ensureOrderConversation($order);

        $messages = $conversation->messages()
            ->with('sender', 'reactions')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $m) => $m->toChatArray());

        return $this->success([
            'conversation' => $this->chat->presentConversation($conversation, null),
            'messages' => $messages,
        ]);
    }

    /** POST /orders/{code}/chat/messages */
    public function send(Request $request, string $code): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $order = $this->resolveOrder($code);
        $conversation = $this->chat->ensureOrderConversation($order);

        $message = $this->chat->sendMessage($conversation, 'customer', null, $data['body']);

        // Let the store know a customer is waiting on them.
        $order->loadMissing('store.users');
        if ($order->store) {
            \App\Services\Notifier::sendMany($order->store->users, 'chat_message', [
                'title' => "Message · Order {$order->code}",
                'body' => \Illuminate\Support\Str::limit($data['body'], 60),
                'url' => '/chat',
                'order_code' => $order->code,
            ]);
        }

        return $this->success($message->toChatArray(), 'Sent.');
    }

    /** POST /orders/{code}/chat/read */
    public function markRead(Request $request, string $code): JsonResponse
    {
        $data = $request->validate(['message_id' => ['required', 'string']]);
        $order = $this->resolveOrder($code);
        $conversation = $this->chat->ensureOrderConversation($order);

        $this->chat->markRead($conversation, 'customer', null, $data['message_id']);

        return $this->success(null, 'Read.');
    }

    /** POST /orders/{code}/chat/messages/{messageId}/reactions */
    public function react(Request $request, string $code, string $messageId): JsonResponse
    {
        $data = $request->validate(['emoji' => ['required', 'string', 'max:16']]);
        $order = $this->resolveOrder($code);
        $conversation = $this->chat->ensureOrderConversation($order);

        $message = ChatMessage::with('conversation')->findOrFail($messageId);
        if ($message->conversation_id !== $conversation->id) {
            return $this->error('Message not found.', 404);
        }

        $this->chat->toggleReaction($message, null, $data['emoji']);

        return $this->success(null, 'Reaction updated.');
    }

    /** POST /orders/{code}/chat/typing */
    public function typing(Request $request, string $code): JsonResponse
    {
        $data = $request->validate(['is_typing' => ['required', 'boolean']]);
        $order = $this->resolveOrder($code);
        $conversation = $this->chat->ensureOrderConversation($order);

        $this->chat->emitTyping($conversation, 'customer', null, null, (bool) $data['is_typing']);

        return $this->success(null);
    }

    private function resolveOrder(string $code): Order
    {
        return Order::where('code', $code)
            ->where('status', '!=', Order::STATUS_DRAFT)
            ->firstOrFail();
    }
}
