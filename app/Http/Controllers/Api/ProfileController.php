<?php

namespace App\Http\Controllers\Api;

use App\Models\ApiToken;
use App\Models\User;
use App\Services\FonnteWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends BaseApiController
{
    public function show(Request $request)
    {
        $user = $this->currentUser($request);

        return $this->ok([
            'user' => $this->userPayload($user),
            'stats' => [
                'booking_count' => $user->bookings()->count(),
                'points' => (int) $user->points,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $this->currentUser($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'current_password' => ['nullable', 'string'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $phone = filled($data['phone'] ?? null)
            ? app(FonnteWhatsAppService::class)->normalizeNumber($data['phone'])
            : null;

        if ($phone && User::where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
            return $this->fail('Nomor WhatsApp sudah digunakan akun lain.', 422, [
                'phone' => ['Nomor WhatsApp sudah digunakan akun lain.'],
            ]);
        }

        $updates = [
            'name' => $data['name'],
            'email' => filled($data['email'] ?? null) ? strtolower($data['email']) : null,
            'phone' => $phone,
        ];

        if (($updates['email'] ?? null) !== $user->email) {
            $updates['email_verified_at'] = null;
        }

        if (filled($data['password'] ?? null)) {
            if (filled($user->password) && ! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
                return $this->fail('Password saat ini tidak sesuai.', 422, [
                    'current_password' => ['Password saat ini tidak sesuai.'],
                ]);
            }

            $updates['password'] = Hash::make($data['password']);
        }

        if ($request->boolean('remove_photo') && $user->profile_photo) {
            $this->deletePhoto($user->profile_photo);
            $updates['profile_photo'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            $this->deletePhoto($user->profile_photo);
            $updates['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $user->update($updates);

        if (filled($data['password'] ?? null)) {
            $currentTokenId = $request->attributes->get('api_token')?->id;
            ApiToken::where('user_id', $user->id)
                ->when($currentTokenId, fn ($query) => $query->where('id', '!=', $currentTokenId))
                ->delete();
        }

        return $this->ok([
            'user' => $this->userPayload($user->fresh()),
        ], 'Profil berhasil diperbarui.');
    }

    public function updatePhoto(Request $request)
    {
        $user = $this->currentUser($request);

        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->deletePhoto($user->profile_photo);

        $path = $request->file('profile_photo')->store('profiles', 'public');
        $user->update(['profile_photo' => $path]);

        return $this->ok([
            'user' => $this->userPayload($user->fresh()),
        ], 'Foto profil berhasil diperbarui.');
    }

    public function destroyPhoto(Request $request)
    {
        $user = $this->currentUser($request);

        $this->deletePhoto($user->profile_photo);
        $user->update(['profile_photo' => null]);

        return $this->ok([
            'user' => $this->userPayload($user->fresh()),
        ], 'Foto profil dihapus.');
    }

    private function deletePhoto(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, 'uploads/profiles/')) {
            $legacyPath = public_path($path);

            if (is_file($legacyPath)) {
                @unlink($legacyPath);
            }

            return;
        }

        Storage::disk('public')->delete($path);
    }
}
