<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::useBootstrapFive();

        // Rate limit semua permintaan API (mobile + guest).
        // Kunci: token Bearer (per perangkat), email/identifier saat login, IP sebagai fallback.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by(
                $request->bearerToken()
                    ?: $request->input('identifier')
                    ?: $request->input('email')
                    ?: $request->ip()
            );
        });

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

        /*
         * Semua halaman yang memakai layout admin membutuhkan data $adminUser,
         * notifikasi, serta peran admin untuk topbar, sidebar, dan header profil.
         */
        View::composer('layouts.admin', function ($view): void {
            $data = $view->getData();
            $adminUser = $data['adminUser'] ?? null;

            if (!$adminUser && session()->has('admin_user_id')) {
                $adminUser = User::find(session('admin_user_id'));
            }

            // Hitung notifikasi secara global untuk semua halaman admin
            if (!isset($data['totalNotificationCount']) || !isset($data['notifications'])) {
                $isSuperadmin = session('admin_role') === 'superadmin';
                $adminUserId = session('admin_user_id');

                $courtQuery = \App\Models\Lapangan::query();
                if (!$isSuperadmin) {
                    $courtQuery->where('owner_admin_id', $adminUserId)
                        ->orWhereHas('venue', fn ($v) => $v->where('owner_admin_id', $adminUserId));
                }
                $courtIds = $courtQuery->select('id');

                $pendingReviewCount = \App\Models\Review::whereIn('lapangan_id', $courtIds)->whereNull('reply_message')->count();
                $pendingPaymentCount = \App\Models\Payment::whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $courtIds))->where('status', '!=', 'Lunas')->count();

                $dbNotifs = $adminUser ? $adminUser->unreadNotifications()->latest()->take(10)->get()->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'type' => 'system',
                        'icon' => 'fa-solid fa-bell',
                        'label' => $n->data['title'] ?? 'Notifikasi Sistem',
                        'meta' => $n->data['message'] ?? '',
                        'time' => optional($n->created_at)->diffForHumans(),
                        'sort_at' => optional($n->created_at)?->timestamp ?? 0,
                        'url' => $n->data['url'] ?? route('admin.dashboard'),
                        'read_at' => $n->read_at,
                    ];
                }) : collect();

                $notifications = collect()
                    ->merge(\App\Models\Review::with(['user', 'lapangan'])
                        ->whereIn('lapangan_id', $courtIds)
                        ->whereNull('reply_message')
                        ->latest()
                        ->get()
                        ->map(function ($review) {
                            return [
                                'type' => 'review',
                                'icon' => 'fa-regular fa-message',
                                'label' => 'Review belum dibalas',
                                'meta' => ($review->user?->name ?? 'User') . ' • ' . ($review->lapangan?->nama ?? 'Lapangan') . ' • rating ' . $review->rating . '/5',
                                'time' => optional($review->created_at)->diffForHumans(),
                                'sort_at' => optional($review->created_at)?->timestamp ?? 0,
                                'url' => route('admin.reviews'),
                            ];
                        }))
                    ->merge(\App\Models\Payment::with(['user', 'booking.lapangan'])
                        ->whereHas('booking.lapangan', fn ($q) => $q->whereIn('id', $courtIds))
                        ->where('status', '!=', 'Lunas')
                        ->latest()
                        ->get()
                        ->map(function ($payment) {
                            return [
                                'type' => 'payment',
                                'icon' => 'fa-solid fa-wallet',
                                'label' => 'Pembayaran belum diverifikasi',
                                'meta' => ($payment->user?->name ?? 'User') . ' • ' . ($payment->booking?->lapangan?->nama ?? 'Lapangan'),
                                'time' => optional($payment->created_at)->diffForHumans(),
                                'sort_at' => optional($payment->created_at)?->timestamp ?? 0,
                                'url' => route('admin.payments'),
                            ];
                        }))
                    ->merge($dbNotifs)
                    ->sortByDesc('sort_at')
                    ->values();

                $unreadDbCount = $adminUser ? $adminUser->unreadNotifications()->count() : 0;
                $totalNotificationCount = $pendingReviewCount + $pendingPaymentCount + $unreadDbCount;

                $view->with('pendingReviewCount', $pendingReviewCount);
                $view->with('pendingPaymentCount', $pendingPaymentCount);
                $view->with('totalNotificationCount', $totalNotificationCount);
                $view->with('notifications', $notifications);
            }

            $view->with('adminUser', $adminUser);
        });
    }
}
