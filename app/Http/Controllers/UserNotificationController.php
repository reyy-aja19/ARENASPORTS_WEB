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
        return User::findOrFail(
            $request->session()->get('admin_user_id')
        );
    }


    public function index(Request $request): View
    {
        $user = $this->currentUser($request);
        $notifications = $user->notifications()->latest()->paginate(15);
        return view('user.notifications', compact('user', 'notifications'));
    }

    public function read(
        Request $request,
        string $notification
    ): RedirectResponse {
        $user = $this->currentUser($request);

        // Pastikan notifikasi benar-benar milik user yang login.
        $item = $user->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        $item->markAsRead();

        $url = $item->data['url'] ?? '/user';

        // Cegah redirect ke website eksternal.
        if (
            !is_string($url) ||
            !str_starts_with($url, '/') ||
            str_starts_with($url, '//')
        ) {
            $url = '/user';
        }

        return redirect($url);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return back()->with(
            'success',
            'Semua notifikasi sudah ditandai dibaca.'
        );
    }
}