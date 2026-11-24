<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    /**
     * Menampilkan halaman permintaan reset password.
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Mengirim tautan reset password ke email.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Password reset email gagal dikirim', [
                'email' => $request->input('email'),
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Gagal mengirim email reset password. Periksa konfigurasi SMTP/mail server.',
            ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Jika email terdaftar, tautan reset password telah dikirim. Silakan cek inbox/spam.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Permintaan reset terlalu sering. Silakan tunggu beberapa saat sebelum mencoba lagi.',
            ]);
        }

        // Jangan bocorkan apakah email terdaftar.
        return back()->with('status', 'Jika email terdaftar, tautan reset password akan dikirim. Silakan cek inbox/spam.');
    }

    /**
     * Menampilkan halaman pengisian password baru.
     */
    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Menyimpan password baru.
     */
    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8),
            ],
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('success', 'Password berhasil diubah. Silakan login menggunakan password baru.');
        }

        return back()->withErrors([
            'email' => __($status),
        ]);
    }
}
