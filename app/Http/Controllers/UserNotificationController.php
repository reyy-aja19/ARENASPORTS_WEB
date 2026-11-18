<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserNotificationController extends Controller
{
    private function currentUser(Request $request): User
    {
        return User::findOrFail($request->session()->get('admin_user_id'));
    }

    public function index(Request $request): View
    {
        $user = $this->currentUser($request);
        $notifications = $user->notifications()->latest()->paginate(15);

        return view('user.notifications', compact('user', 'notifications'));
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $user = $this->currentUser($request);

        // Hanya notifikasi milik akun yang sedang login yang dapat dibaca.
        $item = $user->notifications()->where('id', $notification)->firstOrFail();
        $item->markAsRead();

        $url = $item->data['url'] ?? '/user';

        // Cegah redirect ke alamat eksternal.
        if (!is_string($url) || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            $url = '/user';
        }

        return redirect($url);
    }

    /** Tandai notifikasi sebagai sudah dibaca tanpa membuka tautan tujuannya. */
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $user = $this->currentUser($request);
        $item = $user->notifications()->where('id', $notification)->firstOrFail();

        if ($item->read_at === null) {
            $item->markAsRead();
        }

        return back()->with('success', 'Notifikasi berhasil ditandai sudah dibaca.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi sudah ditandai dibaca.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        // Menghapus hanya notifikasi milik user yang sedang login.
        $deleted = $user->notifications()->delete();

        return redirect()
            ->route('user.notifications')
            ->with('success', $deleted > 0
                ? 'Semua notifikasi berhasil dibersihkan.'
                : 'Tidak ada notifikasi untuk dibersihkan.');
    }

    public function apiNotifications(Request $request)
    {
        $userId = $request->session()->get('admin_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return response()->json(['unreadCount' => 0, 'notifications' => []]);
        }

        $unreadCount = $user->unreadNotifications()->count();
        $notifications = $user->notifications()->latest()->take(6)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notifikasi ArenaSports',
                'message' => $n->data['message'] ?? '',
                'time' => optional($n->created_at)->diffForHumans(),
                'is_read' => $n->read_at !== null,
                'read_url' => route('user.notifications.read', $n->id),
            ];
        });

        return response()->json([
            'unreadCount' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }
}
