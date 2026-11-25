<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Notifications\ArenaUserNotification;
use Illuminate\Http\Request;

class AdminBroadcastController extends BaseApiController
{
    public function store(Request $request)
    {
        $admin = $this->currentUser($request);
        if (! $admin->isAdmin()) {
            return $this->fail('Hanya admin yang dapat mengirim broadcast.', 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1000'],
            'url' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'in:all,users,admins'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $query = User::query()->where('status', 'Aktif');
        $target = $data['target'] ?? 'all';
        $userIds = $data['user_ids'] ?? [];

        if (! empty($userIds)) {
            $query->whereIn('id', $userIds);
        } elseif ($target === 'users') {
            $query->where('role', 'user');
        } elseif ($target === 'admins') {
            $query->whereIn('role', ['admin', 'superadmin', 'venue_admin']);
        }

        $users = $query->get();
        $url = $data['url'] ?? '/notifications';

        foreach ($users as $user) {
            $user->notify(new ArenaUserNotification(
                $data['title'],
                $data['message'],
                $url,
                'broadcast'
            ));
        }

        return $this->ok([
            'sent_count' => $users->count(),
        ], 'Broadcast notifikasi berhasil dikirim.');
    }
}
