<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $this->currentUser($request);

        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $paginator = $user->notifications()->paginate($perPage);

        return $this->ok([
            'notifications' => collect($paginator->items())
                ->map(fn ($notification) => $this->notificationPayload($notification))
                ->values(),
            'unread_count' => $user->unreadNotifications()->count(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function unreadCount(Request $request)
    {
        return $this->ok([
            'unread_count' => $this->currentUser($request)->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification)
    {
        $user = $this->currentUser($request);

        $record = $user->notifications()->whereKey($notification)->first();

        if (! $record) {
            return $this->fail('Notifikasi tidak ditemukan.', 404);
        }

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return $this->ok([
            'notification' => $this->notificationPayload($record->fresh()),
            'unread_count' => $user->unreadNotifications()->count(),
        ], 'Notifikasi ditandai sudah dibaca.');
    }

    public function readAll(Request $request)
    {
        $user = $this->currentUser($request);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return $this->ok([
            'unread_count' => 0,
        ], 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function destroy(Request $request, string $notification)
    {
        $user = $this->currentUser($request);

        $record = $user->notifications()->whereKey($notification)->first();

        if (! $record) {
            return $this->fail('Notifikasi tidak ditemukan.', 404);
        }

        $record->delete();

        return $this->ok([
            'unread_count' => $user->unreadNotifications()->count(),
        ], 'Notifikasi dihapus.');
    }

    public function clear(Request $request)
    {
        $user = $this->currentUser($request);

        $user->notifications()->delete();

        return $this->ok([
            'unread_count' => 0,
        ], 'Semua notifikasi dihapus.');
    }
}
