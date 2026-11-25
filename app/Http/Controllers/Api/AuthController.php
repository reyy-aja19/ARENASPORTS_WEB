<?php

namespace App\Http\Controllers\Api;

use App\Models\ApiToken;
use App\Models\User;
use App\Services\FonnteWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends BaseApiController
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $whatsapp = app(FonnteWhatsAppService::class);
        $phone = filled($data['phone'] ?? null)
            ? $whatsapp->normalizeNumber($data['phone'])
            : null;

        if ($phone && User::where('phone', $phone)->exists()) {
            return $this->fail('Nomor WhatsApp sudah digunakan akun lain.', 422, [
                'phone' => ['Nomor WhatsApp sudah digunakan akun lain.'],
            ]);
        }

        $user = DB::transaction(fn () => User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $phone,
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'status' => 'Aktif',
            'points' => 0,
        ]));

        return $this->ok([
            'token' => $this->createToken($user, $data['device_name'] ?? 'mobile'),
            'user' => $this->userPayload($user),
        ], 'Akun berhasil dibuat. Selamat datang di ARENASPORTS!', 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $identifier = trim((string) (
            $data['email'] ?? $data['identifier'] ?? $data['phone'] ?? ''
        ));

        if ($identifier === '') {
            return $this->fail('Email atau nomor WhatsApp wajib diisi.', 422, [
                'identifier' => ['Email atau nomor WhatsApp wajib diisi.'],
            ]);
        }

        $rateKey = 'api-login:'.strtolower($identifier);

        if (RateLimiter::tooManyAttempts($rateKey, 20)) {
            return $this->fail('Terlalu banyak percobaan masuk. Coba lagi dalam 10 menit.', 429);
        }
        RateLimiter::hit($rateKey, 600);

        $user = User::where('email', strtolower($identifier))->first();

        if (! $user) {
            $normalizedPhone = app(FonnteWhatsAppService::class)
                ->normalizeNumber($identifier);
            $user = User::where('phone', $normalizedPhone)->first();
        }

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            return $this->fail('Email/nomor WhatsApp atau password salah.', 401);
        }

        if ($user->status !== 'Aktif') {
            return $this->fail('Akun sedang nonaktif. Hubungi pengelola.', 403);
        }

        return $this->ok([
            'token' => $this->createToken($user, $data['device_name'] ?? 'mobile'),
            'user' => $this->userPayload($user),
        ], 'Login berhasil.');
    }

    public function me(Request $request)
    {
        return $this->ok([
            'user' => $this->userPayload($this->currentUser($request)),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->attributes->get('api_token');

        if ($token) {
            $token->delete();
        }

        return $this->ok(null, 'Logout berhasil.');
    }

    public function logoutAll(Request $request)
    {
        ApiToken::where('user_id', $this->currentUser($request)->id)->delete();

        return $this->ok(null, 'Semua sesi berhasil diakhiri.');
    }

    public function changePassword(Request $request)
    {
        $user = $this->currentUser($request);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! $user->password || ! Hash::check($data['current_password'], $user->password)) {
            return $this->fail('Password saat ini tidak sesuai.', 422, [
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        $currentTokenId = $request->attributes->get('api_token')?->id;
        ApiToken::where('user_id', $user->id)
            ->when($currentTokenId, fn ($query) => $query->where('id', '!=', $currentTokenId))
            ->delete();

        return $this->ok(null, 'Password berhasil diubah.');
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $emailKey = 'api-forgot:'.$data['email'];

        if (RateLimiter::tooManyAttempts($emailKey, 10)) {
            return $this->fail('Terlalu banyak permintaan reset password. Coba lagi dalam 10 menit.', 429);
        }
        RateLimiter::hit($emailKey, 600);

        $status = Password::sendResetLink(['email' => $data['email']]);

        // Keep the response generic so the endpoint cannot be used to enumerate users.
        return $this->ok([
            'sent' => $status === Password::RESET_LINK_SENT,
        ], 'Jika email terdaftar, tautan reset password sudah dikirim.');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            ApiToken::where('user_id', $user->id)->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return $this->fail('Tautan reset password tidak valid atau sudah kedaluwarsa.', 422);
        }

        return $this->ok(null, 'Password berhasil direset. Silakan login kembali.');
    }

    /**
     * Exchange a Firebase Google ID token for an ArenaSports API token.
     */
    public function google(Request $request)
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $apiKey = (string) config('services.firebase.api_key');

        if ($apiKey === '') {
            return $this->fail('Login Google belum dikonfigurasi di server (FIREBASE_API_KEY).', 503);
        }

        try {
            $response = Http::timeout(15)->post(
                'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key='.$apiKey,
                ['idToken' => $data['id_token']]
            );
        } catch (\Throwable $e) {
            Log::warning('Firebase Google lookup failed: '.$e->getMessage());

            return $this->fail('Login Google gagal. Coba lagi.', 502);
        }

        $account = $response->json('users.0');

        if (! $response->successful() || ! is_array($account)) {
            return $this->fail('Token Google tidak valid atau sudah kedaluwarsa.', 401);
        }

        $email = strtolower(trim((string) ($account['email'] ?? '')));
        $verified = (bool) ($account['emailVerified'] ?? false);

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $verified) {
            return $this->fail('Google harus memberikan email yang valid dan terverifikasi.', 422);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => trim((string) ($account['displayName'] ?? '')) ?: 'Pengguna ArenaSports',
                'email' => $email,
                'password' => Hash::make(Str::random(48)),
                'role' => 'user',
                'status' => 'Aktif',
                'points' => 0,
                'email_verified_at' => now(),
            ]);
        } elseif (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->status !== 'Aktif') {
            return $this->fail('Akun sedang nonaktif. Hubungi pengelola.', 403);
        }

        return $this->ok([
            'token' => $this->createToken($user, $data['device_name'] ?? 'mobile-google'),
            'user' => $this->userPayload($user),
        ], 'Login Google berhasil.');
    }

    /**
     * Request a WhatsApp OTP for login or registration.
     */
    public function requestWhatsAppOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'purpose' => ['nullable', Rule::in(['login', 'register'])],
            'name' => ['nullable', 'string', 'max:255', 'required_if:purpose,register'],
        ]);

        $purpose = $data['purpose'] ?? 'login';
        $whatsapp = app(FonnteWhatsAppService::class);
        $phone = $whatsapp->normalizeNumber($data['phone']);

        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return $this->fail('Format nomor WhatsApp tidak valid.', 422, [
                'phone' => ['Gunakan nomor aktif, misalnya 081234567890.'],
            ]);
        }

        $matches = User::where('phone', $phone)->where('role', 'user')->get();

        if ($matches->count() > 1) {
            return $this->fail('Nomor ini terhubung ke lebih dari satu akun. Hubungi admin.', 422);
        }

        $user = $matches->first();

        if ($purpose === 'login') {
            if (! $user || $user->status !== 'Aktif') {
                return $this->fail('Nomor WhatsApp belum terdaftar atau akun tidak aktif.', 422);
            }
        } else {
            if (User::where('phone', $phone)->exists()) {
                return $this->fail('Nomor WhatsApp sudah terdaftar. Silakan login.', 422);
            }
        }

        $rateKey = 'api-phone-otp-send:'.$phone;

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return $this->fail('Terlalu banyak permintaan OTP. Coba lagi dalam 10 menit.', 429);
        }

        $otp = (string) random_int(100000, 999999);

        try {
            $whatsapp->send(
                $phone,
                "Kode OTP ArenaSports kamu: {$otp}\nKode berlaku selama 5 menit. Jangan bagikan kode ini kepada siapa pun."
            );
        } catch (\Throwable $e) {
            Log::error('Pengiriman WhatsApp OTP gagal: '.$e->getMessage());

            return $this->fail('OTP WhatsApp gagal dikirim. Pastikan Fonnte sudah aktif dan nomor benar.', 502);
        }

        RateLimiter::hit($rateKey, 600);

        $otpToken = Str::random(48);

        Cache::put('api-phone-otp:'.$otpToken, [
            'phone' => $phone,
            'purpose' => $purpose,
            'name' => $data['name'] ?? null,
            'hash' => Hash::make($otp),
        ], now()->addMinutes(5));

        return $this->ok([
            'otp_token' => $otpToken,
            'phone' => $phone,
            'expires_in' => 300,
        ], 'Kode OTP sudah dikirim ke WhatsApp '.$phone.'.');
    }

    public function verifyWhatsAppOtp(Request $request)
    {
        $data = $request->validate([
            'otp_token' => ['required', 'string'],
            'otp' => ['required', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $pending = Cache::get('api-phone-otp:'.$data['otp_token']);

        if (! is_array($pending)) {
            return $this->fail('Sesi OTP tidak ditemukan atau sudah kedaluwarsa. Silakan ulangi.', 422);
        }

        $attemptKey = 'api-phone-otp-verify:'.$pending['phone'];

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            Cache::forget('api-phone-otp:'.$data['otp_token']);

            return $this->fail('Terlalu banyak percobaan OTP. Silakan minta kode baru.', 429);
        }

        RateLimiter::hit($attemptKey, 600);

        if (! Hash::check($data['otp'], $pending['hash'])) {
            return $this->fail('Kode OTP salah atau sudah kedaluwarsa.', 422);
        }

        Cache::forget('api-phone-otp:'.$data['otp_token']);
        RateLimiter::clear($attemptKey);
        RateLimiter::clear('api-phone-otp-send:'.$pending['phone']);

        if ($pending['purpose'] === 'register') {
            if (User::where('phone', $pending['phone'])->exists()) {
                return $this->fail('Nomor WhatsApp sudah terdaftar. Silakan login.', 422);
            }

            $user = User::create([
                'name' => $pending['name'] ?: 'Pengguna ArenaSports',
                'email' => null,
                'phone' => $pending['phone'],
                'password' => Hash::make(Str::random(48)),
                'role' => 'user',
                'status' => 'Aktif',
                'points' => 0,
            ]);
        } else {
            $user = User::where('phone', $pending['phone'])->where('role', 'user')->first();

            if (! $user || $user->status !== 'Aktif') {
                return $this->fail('Akun sedang nonaktif. Hubungi pengelola.', 403);
            }
        }

        return $this->ok([
            'token' => $this->createToken($user, $data['device_name'] ?? 'mobile-whatsapp'),
            'user' => $this->userPayload($user),
        ], 'Verifikasi berhasil. Selamat datang di ArenaSports!');
    }

    private function createToken(User $user, string $deviceName): string
    {
        ApiToken::where('user_id', $user->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        $plainToken = Str::random(64);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => Str::limit($deviceName, 100, ''),
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(90),
        ]);

        return $plainToken;
    }
}
