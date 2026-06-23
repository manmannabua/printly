<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NotificationRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends BaseController
{
    /** GET /my/notifications — the authenticated user's notifications, newest first. */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->notifications();

        if ($request->has('read')) {
            filter_var($request->input('read'), FILTER_VALIDATE_BOOLEAN)
                ? $query->whereNotNull('read_at')
                : $query->whereNull('read_at');
        }
        if ($request->filled('type')) {
            $query->where('type', 'like', '%' . $request->input('type') . '%');
        }

        $page = $query->latest()->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'data' => collect($page->items())->map(fn (DatabaseNotification $n) => $this->present($n)),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'has_more' => $page->hasMorePages(),
            ],
        ]);
    }

    /** GET /my/notifications/unread-count */
    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    /** POST /my/notifications/{id}/read */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);
        if (!$notification) {
            return $this->error('Notification not found.', 404);
        }

        $notification->markAsRead();
        $notification = $notification->fresh();

        NotificationRead::dispatch(
            (string) $request->user()->id,
            $notification->id,
            $notification->read_at?->toIso8601String(),
        );

        return $this->success($this->present($notification), 'Notification marked as read.');
    }

    /** POST /my/notifications/read-all */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $unreadIds = $user->unreadNotifications()->pluck('id')->all();
        $user->unreadNotifications()->update(['read_at' => now()]);

        foreach ($unreadIds as $id) {
            NotificationRead::dispatch((string) $user->id, $id, now()->toIso8601String());
        }

        return $this->success(null, 'All notifications marked as read.');
    }

    /** DELETE /my/notifications/{id} */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);
        if (!$notification) {
            return $this->error('Notification not found.', 404);
        }
        $notification->delete();

        return $this->success(null, 'Notification deleted.');
    }

    /** DELETE /my/notifications/read — clear all read notifications. */
    public function destroyRead(Request $request): JsonResponse
    {
        $deleted = $request->user()->notifications()->whereNotNull('read_at')->delete();

        return $this->success(['deleted_count' => $deleted], 'Read notifications deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(DatabaseNotification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->type,
            'data' => $n->data,
            'read_at' => $n->read_at?->toIso8601String(),
            'is_read' => $n->read_at !== null,
            'created_at' => $n->created_at->toIso8601String(),
        ];
    }
}
