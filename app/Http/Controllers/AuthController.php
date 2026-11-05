<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AccountActivityNotification;
use App\Services\FonnteWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('admin_logged_in')) {
            return $this->redirectByRole($request);
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !$user->password || !Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('email'))->with('error', 'Email atau password salah.');
        }

        if ($user->status !== 'Aktif') {
            return back()->withInput($request->only('email'))->with('error', 'Akun sedang nonaktif. Hubungi pengelola.');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'admin_logged_in' => true,
            'admin_user_id' => $user->id,
            'admin_role' => $user->role,
        ]);

        $this->sendAccountActivity($user, 'Login berhasil', 'Akun ArenaSports kamu baru saja digunakan untuk login.', 'Email dan password');

        return $this->redirectAfterLogin($request);
    }

    public function showRegister(Request $request)
    {
        if ($request->session()->get('admin_logged_in')) {
            return $this->redirectByRole($request);
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $phone = !empty($data['phone']) ? app(FonnteWhatsAppService::class)->normalizeNumber($data['phone']) : null;
        if ($phone && User::where('phone', $phone)->exists()) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['phone' => 'Nomor WhatsApp sudah digunakan akun lain.']);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $phone,
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'status' => 'Aktif',
            'points' => 0,
        ]);

        $request->session()->regenerate();
        $request->session()->put([
            'admin_logged_in' => true,
            'admin_user_id' => $user->id,
            'admin_role' => 'user',
        ]);

        $this->sendAccountActivity($user, 'Pendaftaran berhasil', 'Akun ArenaSports kamu berhasil didaftarkan.', 'Email dan password');

        return $this->redirectAfterLogin($request)->with('success', 'Akun berhasil dibuat. Selamat datang di ARENASPORTS!');
    }

    public function redirectToGoogle()
    {
        abort_if(
            !config('services.google.client_id') || !config('services.google.client_secret'),
            503,
            'Google OAuth belum dikonfigurasi di .env.'
        );

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth gagal: '.$e->getMessage());
            return redirect()->route('login')->with('error', 'Login Google gagal. Periksa konfigurasi OAuth dan callback URL.');
        }

        $googleEmail = $google->getEmail();
        $googleEmailVerified = filter_var(
            data_get($google->getRaw(), 'verified_email', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$googleEmail || !filter_var($googleEmail, FILTER_VALIDATE_EMAIL) || !$googleEmailVerified) {
            return redirect()->route('login')->with(
                'error',
                'Google harus memberikan alamat email yang valid dan sudah terverifikasi.'
            );
        }

        // Hanya email Google yang terverifikasi yang boleh ditautkan ke akun lokal.
        $user = User::where('email', $googleEmail)->first();
        $isNew = !$user;

        if (!$user) {
            $user = User::create([
                'name' => $google->getName() ?: 'Pengguna ArenaSports',
                'email' => $googleEmail,
                'password' => Hash::make(Str::random(48)),
                'role' => 'user',
                'status' => 'Aktif',
                'points' => 0,
                'email_verified_at' => now(),
            ]);
        } elseif (!$user->email_verified_at && data_get($google->getRaw(), 'verified_email')) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->status !== 'Aktif') {
            return redirect()->route('login')->with('error', 'Akun sedang nonaktif.');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'admin_logged_in' => true,
            'admin_user_id' => $user->id,
            'admin_role' => $user->role,
        ]);

        $this->sendAccountActivity(
            $user,
            $isNew ? 'Pendaftaran Google berhasil' : 'Login Google berhasil',
            $isNew ? 'Akun ArenaSports kamu berhasil dibuat menggunakan Google.' : 'Akun ArenaSports kamu baru saja digunakan untuk login menggunakan Google.',
            'Google'
        );

        return $this->redirectAfterLogin($request);
    }

    public function showPhoneLogin()
    {
        return view('auth.phone', ['mode' => 'login']);
    }

    public function showPhoneRegister()
    {
        return view('auth.phone', ['mode' => 'register']);
    }

    public function sendPhoneLoginOtp(Request $request, FonnteWhatsAppService $whatsapp)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:50']]);
        $phone = $whatsapp->normalizeNumber($data['phone']);
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return back()->withInput()->withErrors(['phone' => 'Format nomor WhatsApp tidak valid. Gunakan nomor aktif, misalnya 081234567890.']);
        }

        $phoneMatches = User::where('phone', $phone)->where('role', 'user')->get();
        if ($phoneMatches->count() > 1) {
            return back()->withInput()->withErrors(['phone' => 'Nomor ini terhubung ke lebih dari satu akun. Hubungi admin untuk memperbaiki data nomor.']);
        }
        $user = $phoneMatches->first();

        if (!$user || $user->status !== 'Aktif') {
            return back()->withInput()->withErrors(['phone' => 'Nomor WhatsApp belum terdaftar atau akun tidak aktif.']);
        }

        return $this->issuePhoneOtp($request, $whatsapp, $phone, 'login', null);
    }

    public function sendPhoneRegisterOtp(Request $request, FonnteWhatsAppService $whatsapp)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
        ]);
        $phone = $whatsapp->normalizeNumber($data['phone']);

        if (strlen($phone) < 10 || strlen($phone) > 15) {
            return back()->withInput()->withErrors(['phone' => 'Format nomor WhatsApp tidak valid. Gunakan nomor aktif, misalnya 081234567890.']);
        }

        if (User::where('phone', $phone)->exists()) {
            return back()->withInput()->withErrors(['phone' => 'Nomor WhatsApp sudah terdaftar. Silakan login.']);
        }

        return $this->issuePhoneOtp($request, $whatsapp, $phone, 'register', $data['name']);
    }

    private function issuePhoneOtp(Request $request, FonnteWhatsAppService $whatsapp, string $phone, string $purpose, ?string $name)
    {
        $rateKey = 'phone-otp-send:'.$phone;
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return back()->withErrors(['phone' => 'Terlalu banyak permintaan OTP. Coba lagi dalam 10 menit.']);
        }

        try {
            $otp = (string) random_int(100000, 999999);
            $whatsapp->send($phone, "Kode OTP ArenaSports kamu: {$otp}\nKode berlaku selama 5 menit. Jangan bagikan kode ini kepada siapa pun.");
        } catch (\Throwable $e) {
            Log::error('Pengiriman WhatsApp OTP gagal: '.$e->getMessage());
            return back()->withInput()->withErrors(['phone' => 'OTP WhatsApp gagal dikirim. Pastikan Fonnte sudah aktif dan nomor benar.']);
        }

        RateLimiter::hit($rateKey, 600);
        Cache::put('phone-otp:'.hash('sha256', $phone), Hash::make($otp), now()->addMinutes(5));
        $request->session()->put('phone_otp_pending', [
            'phone' => $phone,
            'purpose' => $purpose,
            'name' => $name,
        ]);

        return redirect()->route('phone.verify.form')->with('success', 'Kode OTP sudah dikirim ke WhatsApp '.$phone.'.');
    }

    public function showPhoneVerify(Request $request)
    {
        abort_unless($request->session()->has('phone_otp_pending'), 403, 'Silakan minta kode OTP terlebih dahulu.');
        return view('auth.phone-verify');
    }

    public function verifyPhoneOtp(Request $request, FonnteWhatsAppService $whatsapp)
    {
        $request->validate(['otp' => ['required', 'digits:6']]);
        $pending = $request->session()->get('phone_otp_pending');

        if (!$pending || !isset($pending['phone'], $pending['purpose'])) {
            return redirect()->route('login')->with('error', 'Sesi OTP tidak ditemukan. Silakan ulangi.');
        }

        $attemptKey = 'phone-otp-verify:'.$pending['phone'];
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            $request->session()->forget('phone_otp_pending');
            return redirect()->route('login')->with('error', 'Terlalu banyak percobaan OTP. Silakan minta kode baru.');
        }
        RateLimiter::hit($attemptKey, 600);

        $cacheKey = 'phone-otp:'.hash('sha256', $pending['phone']);
        $storedHash = Cache::get($cacheKey);
        if (!$storedHash || !Hash::check($request->otp, $storedHash)) {
            return back()->withErrors(['otp' => 'Kode OTP salah atau sudah kedaluwarsa.']);
        }

        Cache::forget($cacheKey);
        RateLimiter::clear($attemptKey);
        RateLimiter::clear('phone-otp-send:'.$pending['phone']);

        if ($pending['purpose'] === 'register') {
            if (User::where('phone', $pending['phone'])->exists()) {
                $request->session()->forget('phone_otp_pending');
                return redirect()->route('login')->with('error', 'Nomor WhatsApp sudah terdaftar. Silakan login.');
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
            $activityTitle = 'Pendaftaran WhatsApp berhasil';
            $activityMessage = 'Akun ArenaSports kamu berhasil dibuat menggunakan nomor WhatsApp.';
        } else {
            $user = User::where('phone', $pending['phone'])->where('role', 'user')->firstOrFail();
            if ($user->status !== 'Aktif') {
                $request->session()->forget('phone_otp_pending');
                return redirect()->route('login')->with('error', 'Akun sedang nonaktif.');
            }
            $activityTitle = 'Login WhatsApp berhasil';
            $activityMessage = 'Akun ArenaSports kamu baru saja digunakan untuk login menggunakan OTP WhatsApp.';
        }

        $request->session()->forget('phone_otp_pending');
        $request->session()->regenerate();
        $request->session()->put([
            'admin_logged_in' => true,
            'admin_user_id' => $user->id,
            'admin_role' => $user->role,
        ]);

        $this->sendAccountActivity($user, $activityTitle, $activityMessage, 'WhatsApp OTP');

        return $this->redirectAfterLogin($request)->with('success', 'Verifikasi berhasil. Selamat datang di ArenaSports!');
    }

    private function sendAccountActivity(User $user, string $subject, string $message, string $method): void
    {
        $time = now()->timezone('Asia/Jakarta')->format('d-m-Y H:i:s');

        if ($user->email) {
            try {
                $user->notify(new AccountActivityNotification($subject, $message, $method, $time));
            } catch (\Throwable $e) {
                Log::warning('Email aktivitas akun gagal dikirim: '.$e->getMessage());
            }
        }

        if ($user->phone && app(FonnteWhatsAppService::class)->configured()) {
            try {
                app(FonnteWhatsAppService::class)->send(
                    $user->phone,
                    "ArenaSports - {$subject}\n{$message}\nMetode: {$method}\nWaktu: {$time}\nJika bukan kamu, segera amankan akun."
                );
            } catch (\Throwable $e) {
                Log::warning('Notifikasi WhatsApp aktivitas akun gagal dikirim: '.$e->getMessage());
            }
        }
    }

    private function redirectAfterLogin(Request $request)
    {
        $role = $request->session()->get('admin_role');
        if ($role === 'user') {
            $intended = $request->session()->pull('url.intended');
            if (is_string($intended) && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
                return redirect()->to($intended);
            }
        }
        return $this->redirectByRole($request);
    }

    private function redirectByRole(Request $request)
    {
        $role = $request->session()->get('admin_role');

        switch ($role) {
            case 'user':
                return redirect()->route('user.home');
            case 'superadmin':
            case 'admin':
            case 'venue_admin':
                return redirect()->route('admin.dashboard');
            default:
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('error', 'Role akun tidak dikenali. Silakan login kembali.');
        }
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'Kamu berhasil keluar dari akun.');
    }
}
