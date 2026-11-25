<?php

namespace App\Http\Controllers\Api;

use App\Models\UserDeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends BaseApiController
{
    public function store(Request $request)
    {
        $user = $this->currentUser($request);
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:32'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $token = $data['token'];

        UserDeviceToken::where('token', $token)->where('user_id', '!=', $user->id)->delete();

        UserDeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $data['platform'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        // Keep only the most recent 10 tokens per user.
        $staleIds = UserDeviceToken::where('user_id', $user->id)
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->skip(10)
            ->pluck('id');
        if ($staleIds->isNotEmpty()) {
            UserDeviceToken::whereIn('id', $staleIds)->delete();
        }

        return $this->ok(null, 'Device token disimpan.');
    }

    public function destroy(Request $request)
    {
        $user = $this->currentUser($request);
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        UserDeviceToken::where('user_id', $user->id)
            ->where('token', $data['token'])
            ->delete();

        return $this->ok(null, 'Device token dihapus.');
    }
}
