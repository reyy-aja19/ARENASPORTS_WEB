<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (
            $this->app->environment('production') ||
            request()->header('X-Forwarded-Proto') === 'https'
        ) {
            URL::forceScheme('https');
        }

        /*
         * Semua halaman yang memakai layout pengguna membutuhkan profil
         * pengguna aktif (nama dan saldo poin). Sebagian halaman lama belum
         * mengirimkan variabel $user dari controller, sehingga ambil dari
         * ID sesi yang sudah divalidasi oleh middleware CekLogin.
         */
        View::composer('layouts.user', function ($view): void {
            $user = $view->getData()['user'] ?? null;

            if (!$user && session()->has('admin_user_id')) {
                $user = User::find(session('admin_user_id'));
            }

            $view->with('user', $user);
        });
    }
}
