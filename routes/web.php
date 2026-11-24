<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ChatbotSettingsController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Middleware\CekLogin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HALAMAN UMUM
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/dashboard', function () {
    if (!session('admin_logged_in') || !session('admin_user_id')) {
        return redirect()->route('login');
    }

    if (session('admin_role') === 'user') {
        return redirect()->route('user.home');
    }

    return redirect()->route('admin.dashboard');
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| LOGIN, REGISTER, GOOGLE LOGIN, LOGOUT
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])
    ->name('auth.google.callback');

Route::get('/auth/apple/redirect', [AuthController::class, 'redirectToApple'])
    ->name('auth.apple.redirect');

Route::match(['get', 'post'], '/auth/apple/callback', [AuthController::class, 'handleAppleCallback'])
    ->name('auth.apple.callback');

Route::get('/register', [AuthController::class, 'showRegister'])
    ->name('register');

Route::post('/register', [AuthController::class, 'register'])
    ->name('register.submit');

// Login dan pendaftaran menggunakan OTP WhatsApp
Route::get('/login/whatsapp', [AuthController::class, 'showPhoneLogin'])
    ->name('phone.login');

Route::post('/login/whatsapp', [AuthController::class, 'sendPhoneLoginOtp'])
    ->name('phone.login.send');

Route::get('/register/whatsapp', [AuthController::class, 'showPhoneRegister'])
    ->name('phone.register');

Route::post('/register/whatsapp', [AuthController::class, 'sendPhoneRegisterOtp'])
    ->name('phone.register.send');

Route::get('/verify-whatsapp-otp', [AuthController::class, 'showPhoneVerify'])
    ->name('phone.verify.form');

Route::post('/verify-whatsapp-otp', [AuthController::class, 'verifyPhoneOtp'])
    ->name('phone.verify');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout.perform');

// Password Reset
Route::get('/forgot-password', [PasswordResetController::class, 'create'])
    ->name('password.request');

Route::post('/forgot-password', [PasswordResetController::class, 'store'])
    ->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'update'])
    ->name('password.update');

/*
|--------------------------------------------------------------------------
| LOGOUT SUCCESS
|--------------------------------------------------------------------------
*/

Route::get('/logout-success', [AdminController::class, 'logoutSuccess'])
    ->name('logout.success');

/*
|--------------------------------------------------------------------------
| MIDTRANS NOTIFICATION
|--------------------------------------------------------------------------
*/

Route::post('/payments/midtrans/notification', [
    MidtransController::class,
    'notification'
])->name('midtrans.notification');

/*
|--------------------------------------------------------------------------
| ROUTE PUBLIK MARKETPLACE
|--------------------------------------------------------------------------
*/

Route::prefix('user')->name('user.')->group(function () {

    Route::get('/venue', [MarketplaceController::class, 'venues'])
        ->name('venues');

    Route::get('/venue/{venue}', [MarketplaceController::class, 'venueDetail'])
        ->whereNumber('venue')
        ->name('venues.show');

    Route::get('/lapangan', [UserController::class, 'courts'])
        ->name('courts');

    Route::get('/lapangan/{lapangan}/availability', [
        MarketplaceController::class,
        'availability'
    ])->name('courts.availability');

    Route::get('/open-match/create', [UserController::class, 'createOpenMatch'])
        ->name('openmatches.create');

    Route::get('/open-match', [UserController::class, 'openMatches'])
        ->name('openmatches');
});

Route::get('/lapangan/{lapangan}', [
    UserController::class,
    'courtDetail'
])->name('courts.detail');

/*
|--------------------------------------------------------------------------
| ROUTE USER TERLOGIN
|--------------------------------------------------------------------------
*/

Route::middleware([CekLogin::class, 'role:user'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | NOTIFIKASI
        |--------------------------------------------------------------------------
        */

        Route::get('/notifikasi', [
            UserNotificationController::class,
            'index'
        ])->name('notifications');

        Route::post('/notifikasi/{notification}/read', [
            UserNotificationController::class,
            'read'
        ])->name('notifications.read');

        Route::post('/notifikasi/{notification}/mark-read', [
            UserNotificationController::class,
            'markRead'
        ])->name('notifications.mark-read');

        Route::post('/notifikasi/read-all', [
            UserNotificationController::class,
            'readAll'
        ])->name('notifications.read-all');

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD USER
        |--------------------------------------------------------------------------
        */

        Route::get('/', [UserController::class, 'home'])
            ->name('home');
        Route::delete('/notifikasi/clear', [
            UserNotificationController::class,
            'clear'
        ])->name('notifications.clear');
        Route::delete('/notifikasi/{notification}', [
            UserNotificationController::class,
            'delete'
        ])->name('notifications.delete');

        /*
        |--------------------------------------------------------------------------
        | BOOKING LAPANGAN
        |--------------------------------------------------------------------------
        */

        Route::post('/lapangan/{lapangan}/booking', [
            UserController::class,
            'bookingStore'
        ])->name('booking.store');

        Route::get('/booking', [UserController::class, 'bookings'])
            ->name('bookings');

        Route::post('/booking/{booking}/batalkan', [
            UserController::class,
            'bookingCancel'
        ])->name('bookings.cancel');

        Route::get('/booking/{booking}/tiket', [
            UserController::class,
            'bookingTicket'
        ])->name('bookings.ticket');

        Route::post('/booking/{booking}/review', [
            UserController::class,
            'reviewStore'
        ])->name('reviews.store');

        /*
        |--------------------------------------------------------------------------
        | PERPANJANGAN BOOKING USER
        |--------------------------------------------------------------------------
        */

        Route::post('/booking/{booking}/perpanjang', [
            UserController::class,
            'bookingExtensionStore'
        ])->name('booking-extensions.store');

        Route::get('/perpanjangan/{extension}/midtrans', [
            MidtransController::class,
            'extensionCheckout'
        ])->name('booking-extensions.midtrans');

        /*
        |--------------------------------------------------------------------------
        | MIDTRANS BOOKING AWAL
        |--------------------------------------------------------------------------
        */

        Route::get('/booking/{booking}/midtrans', [
            MidtransController::class,
            'checkout'
        ])->name('midtrans.start');

        Route::post('/booking/{booking}/midtrans', [
            MidtransController::class,
            'checkout'
        ])->name('midtrans.checkout');

        /*
        |--------------------------------------------------------------------------
        | PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        Route::get('/pembayaran', function () {
            return redirect()->route('user.bookings');
        })->name('payments');

        Route::get('/pembayaran/{payment}/status', [
            MidtransController::class,
            'paymentStatus'
        ])->name('payments.status');

        Route::get('/pembayaran/{payment}/bayar', [
            MidtransController::class,
            'paymentPage'
        ])->name('payments.pay');

        Route::get('/pembayaran/{payment}/berhasil', [
            MidtransController::class,
            'paymentSuccess'
        ])->name('payments.success');

        Route::post('/pembayaran/{payment}/bukti', [
            UserController::class,
            'paymentProof'
        ])->name('payments.proof');

        /*
        |--------------------------------------------------------------------------
        | OPEN MATCH
        |--------------------------------------------------------------------------
        */

        Route::post('/open-match', [
            UserController::class,
            'openMatchStore'
        ])->name('openmatches.store');

        Route::post('/open-match/{openMatch}/join', [
            UserController::class,
            'joinMatch'
        ])->name('openmatches.join');

        Route::get('/open-match/{openMatch}/chat', [
            UserController::class,
            'openMatchChat'
        ])->name('openmatches.chat');

        Route::get('/open-match/{openMatch}/chat/messages', [
            UserController::class,
            'openMatchChatMessages'
        ])->name('openmatches.chat.messages');

        Route::post('/open-match/{openMatch}/chat', [
            UserController::class,
            'openMatchChatSend'
        ])->name('openmatches.chat.send');

        /*
        |--------------------------------------------------------------------------
        | EVENT & TURNAMEN RESMI
        |--------------------------------------------------------------------------
        */

        Route::post('/event/{event}/daftar', [
            UserController::class,
            'eventRegister'
        ])->name('events.register');

        /*
        |--------------------------------------------------------------------------
        | TUKAR POIN
        |--------------------------------------------------------------------------
        */

        Route::get('/tukar-poin', [UserController::class, 'rewards'])
            ->name('rewards');

        Route::post('/tukar-poin/{reward}', [
            UserController::class,
            'redeem'
        ])->name('rewards.redeem');

        /*
        |--------------------------------------------------------------------------
        | PROFIL USER
        |--------------------------------------------------------------------------
        */

        Route::get('/profil', [UserController::class, 'profile'])
            ->name('profile');

        Route::put('/profil', [
            UserController::class,
            'profileUpdate'
        ])->name('profile.update');

        Route::get('/api/notifications', [
            UserNotificationController::class,
            'apiNotifications'
        ])->name('api.notifications');
    });

/*
|--------------------------------------------------------------------------
| PENGAJUAN VENUE
|--------------------------------------------------------------------------
*/

Route::middleware([CekLogin::class, 'role:user,venue_admin'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {

        Route::get('/venue/apply', [
            MarketplaceController::class,
            'applicationForm'
        ])->name('venue-application');

        Route::post('/venue/apply', [
            MarketplaceController::class,
            'applicationStore'
        ])->name('venue-application.store');
    });

/*
|--------------------------------------------------------------------------
| ROUTE ADMIN DAN ADMIN VENUE
|--------------------------------------------------------------------------
*/

Route::middleware([CekLogin::class, 'role:superadmin,venue_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | DASHBOARD
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [AdminController::class, 'dashboard'])
            ->name('dashboard');

        // Notifikasi Admin dan Superadmin
        Route::get('/notifikasi', [AdminController::class, 'notifications'])
            ->name('notifications');
        Route::post('/notifikasi/{notification}/read', [AdminController::class, 'notificationRead'])
            ->name('notifications.read');
        Route::post('/notifikasi/{notification}/mark-read', [AdminController::class, 'notificationMarkRead'])
            ->name('notifications.mark-read');
        Route::post('/notifikasi/read-all', [AdminController::class, 'notificationsReadAll'])
            ->name('notifications.read-all');
        Route::delete('/notifikasi/clear', [AdminController::class, 'notificationsClear'])
            ->name('notifications.clear');
        Route::delete('/notifikasi/{notification}', [AdminController::class, 'notificationDelete'])
            ->name('notifications.delete');
        Route::get('/api/notifications', [AdminController::class, 'apiNotifications'])
            ->name('api.notifications');

        /*
        |--------------------------------------------------------------------------
        | PROFIL DAN KEUANGAN VENUE
        |--------------------------------------------------------------------------
        */

        Route::get('/venue-register', [
            MarketplaceController::class,
            'applicationForm'
        ])->name('venue-register');

        Route::post('/venue-register', [
            MarketplaceController::class,
            'applicationStore'
        ])->name('venue-register.store');

        Route::get('/venue-finance', [
            MarketplaceController::class,
            'finance'
        ])->name('venue-finance');

        Route::get('/venue-profile', [
            MarketplaceController::class,
            'venueProfile'
        ])->name('venue-profile');

        Route::put('/venue-profile', [
            MarketplaceController::class,
            'venueProfileUpdate'
        ])->name('venue-profile.update');

        Route::post('/venue-finance/withdraw', [
            MarketplaceController::class,
            'requestWithdrawal'
        ])->name('venue-finance.withdraw');

        /*
        |--------------------------------------------------------------------------
        | KHUSUS SUPERADMIN
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:superadmin')->group(function () {

            // Pengajuan venue
            Route::get('/venue-applications', [
                MarketplaceController::class,
                'applications'
            ])->name('venue-applications');

            Route::get('/venue-applications/{application}/documents/{document}', [
                MarketplaceController::class,
                'applicationDocument'
            ])->name('venue-applications.documents.download');

            Route::get('/venue-applications/{application}/documents/{document}/preview', [
                MarketplaceController::class,
                'applicationDocumentPreview'
            ])->name('venue-applications.documents.preview');

            Route::post('/venue-applications/{application}/approve', [
                MarketplaceController::class,
                'approveApplication'
            ])->name('venue-applications.approve');

            Route::post('/venue-applications/{application}/revision', [
                MarketplaceController::class,
                'requestRevision'
            ])->name('venue-applications.revision');

            Route::post('/venue-applications/{application}/reject', [
                MarketplaceController::class,
                'rejectApplication'
            ])->name('venue-applications.reject');

            // Penarikan dana
            Route::get('/venue-withdrawals', [
                MarketplaceController::class,
                'withdrawals'
            ])->name('venue-withdrawals');

            Route::post('/venue-withdrawals/{withdrawal}/process', [
                MarketplaceController::class,
                'processWithdrawal'
            ])->name('venue-withdrawals.process');

            // Manajemen venue (superadmin)
            Route::get('/venues-manage', [AdminController::class, 'venuesManage'])
                ->name('venues.manage');
            Route::get('/venues-manage/{venue}', [AdminController::class, 'venueManageShow'])
                ->name('venues.manage.show');

            // Rewards
            Route::get('/rewards', [AdminController::class, 'rewards'])
                ->name('rewards');

            Route::post('/rewards', [AdminController::class, 'rewardStore'])
                ->name('rewards.store');

            Route::get('/rewards/{reward}/edit', [
                AdminController::class,
                'rewardEdit'
            ])->name('rewards.edit');

            Route::put('/rewards/{reward}', [
                AdminController::class,
                'rewardUpdate'
            ])->name('rewards.update');

            Route::post('/rewards/{reward}/toggle', [
                AdminController::class,
                'rewardToggle'
            ])->name('rewards.toggle');

            Route::delete('/rewards/{reward}', [
                AdminController::class,
                'rewardDelete'
            ])->name('rewards.delete');

            // Chatbot
            Route::get('/chatbot-settings', [
                ChatbotSettingsController::class,
                'edit'
            ])->name('chatbot.edit');

            Route::put('/chatbot-settings', [
                ChatbotSettingsController::class,
                'update'
            ])->name('chatbot.update');

            // Manajemen user
            Route::get('/users', [AdminController::class, 'users'])
                ->name('users');

            Route::get('/users/create', [
                AdminController::class,
                'userCreate'
            ])->name('users.create');

            Route::post('/users', [
                AdminController::class,
                'userStore'
            ])->name('users.store');

            Route::get('/users/{user}/edit', [
                AdminController::class,
                'userEdit'
            ])->name('users.edit');

            Route::put('/users/{user}', [
                AdminController::class,
                'userUpdate'
            ])->name('users.update');

            Route::post('/users/{user}/toggle', [
                AdminController::class,
                'userToggle'
            ])->name('users.toggle');

            Route::delete('/users/{user}', [
                AdminController::class,
                'userDelete'
            ])->name('users.delete');
        });

        /*
        |--------------------------------------------------------------------------
        | OPEN MATCH & EVENT RESMI (SUPERADMIN & VENUE ADMIN)
        |--------------------------------------------------------------------------
        */

        Route::get('/control-center', [
            AdminController::class,
            'controlCenter'
        ])->name('control-center');

        Route::resource('/events', EventController::class)
            ->except(['show'])
            ->names('events');

        Route::get('/events/{event}/pendaftar', [
            EventController::class,
            'registrations'
        ])->name('events.registrations');

        Route::post('/events/{event}/pendaftar/{registration}/status', [
            EventController::class,
            'updateRegistrationStatus'
        ])->name('events.registrations.status');

        Route::get('/open-match', [
            AdminController::class,
            'openMatches'
        ])->name('openmatches');

        Route::post('/open-match', [
            AdminController::class,
            'openMatchStore'
        ])->name('openmatches.store');

        Route::put('/open-match/{openMatch}', [
            AdminController::class,
            'openMatchUpdate'
        ])->name('openmatches.update');

        Route::post('/open-match/{openMatch}/toggle', [
            AdminController::class,
            'openMatchToggle'
        ])->name('openmatches.toggle');

        Route::delete('/open-match/{openMatch}', [
            AdminController::class,
            'openMatchDelete'
        ])->name('openmatches.delete');

        /*
        |--------------------------------------------------------------------------
        | MANAJEMEN LAPANGAN
        |--------------------------------------------------------------------------
        */

        Route::get('/lapangan', [AdminController::class, 'courts'])
            ->name('courts');

        Route::get('/lapangan/create', [
            AdminController::class,
            'courtCreate'
        ])->name('courts.create');

        Route::post('/lapangan', [
            AdminController::class,
            'courtStore'
        ])->name('courts.store');

        Route::get('/lapangan/{lapangan}/edit', [
            AdminController::class,
            'courtEdit'
        ])->name('courts.edit');

        Route::put('/lapangan/{lapangan}', [
            AdminController::class,
            'courtUpdate'
        ])->name('courts.update');

        Route::delete('/lapangan/{lapangan}', [
            AdminController::class,
            'courtDelete'
        ])->name('courts.delete');

        /*
        |--------------------------------------------------------------------------
        | MANAJEMEN BOOKING
        |--------------------------------------------------------------------------
        */

        Route::get('/booking', [AdminController::class, 'bookings'])
            ->name('bookings');

        Route::post('/booking', [AdminController::class, 'bookingStore'])
            ->name('bookings.store');

        Route::post('/booking/{booking}/toggle', [
            AdminController::class,
            'bookingToggle'
        ])->name('bookings.toggle');

        Route::delete('/booking/{booking}', [
            AdminController::class,
            'bookingDelete'
        ])->name('bookings.delete');

        /*
        |--------------------------------------------------------------------------
        | MANAJEMEN REVIEW
        |--------------------------------------------------------------------------
        */

        Route::get('/reviews', [AdminController::class, 'reviews'])
            ->name('reviews');

        Route::post('/reviews/{review}/reply', [
            AdminController::class,
            'reviewReply'
        ])->name('reviews.reply');

        Route::post('/reviews/{review}/toggle', [
            AdminController::class,
            'reviewToggle'
        ])->name('reviews.toggle');

        Route::delete('/reviews/{review}', [
            AdminController::class,
            'reviewDelete'
        ])->name('reviews.delete');

        /*
        |--------------------------------------------------------------------------
        | MANAJEMEN PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        Route::get('/payments', [AdminController::class, 'payments'])
            ->name('payments');

        Route::post('/payments/{payment}/verify', [
            AdminController::class,
            'paymentVerify'
        ])->name('payments.verify');

        /*
        |--------------------------------------------------------------------------
        | LAPORAN
        |--------------------------------------------------------------------------
        */

        Route::get('/reports', [AdminController::class, 'reports'])
            ->name('reports');

        Route::get('/reports/export/csv', [
            AdminController::class,
            'exportReportsCsv'
        ])->name('reports.export.csv');

        Route::get('/reports/export/excel', [
            AdminController::class,
            'exportReportsExcel'
        ])->name('reports.export.excel');

        Route::get('/export/csv', [
            AdminController::class,
            'exportReportsCsv'
        ])->name('export.csv');

        Route::get('/export/excel', [
            AdminController::class,
            'exportReportsExcel'
        ])->name('export.excel');

        /*
        |--------------------------------------------------------------------------
        | PROFIL ADMIN
        |--------------------------------------------------------------------------
        */

        Route::get('/profile', [AdminController::class, 'profile'])
            ->name('profile');

        Route::post('/profile', [
            AdminController::class,
            'profileUpdate'
        ])->name('profile.update');

        Route::get('/api/notifications', [
            AdminController::class,
            'apiNotifications'
        ])->name('api.notifications');
        Route::post('/booking/{booking}/perpanjang-manual', [
            AdminController::class,
            'bookingExtensionManual'
        ])->name('booking-extensions.manual');

        /*
        |--------------------------------------------------------------------------
        | LOGOUT ADMIN
        |--------------------------------------------------------------------------
        */

        Route::get('/logout', [AdminController::class, 'logoutConfirm'])
            ->name('logout');
    });

    Route::get('/chatbot/nearby-venues', [
    ChatbotController::class,
    'nearbyVenues'
])->name('chatbot.nearby-venues');

    Route::post('/chatbot/reply', [
        ChatbotController::class,
        'reply'
    ])->name('chatbot.reply');
