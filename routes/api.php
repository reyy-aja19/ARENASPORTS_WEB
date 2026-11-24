<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OpenMatchController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\VenueApplicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ARENASPORTS MOBILE API
|--------------------------------------------------------------------------
| Semua endpoint mengembalikan JSON dengan envelope:
| { success: bool, message: ?string, data: mixed, errors?: mixed }
|--------------------------------------------------------------------------
*/

Route::get('/health', fn () => response()->json([
    'success' => true,
    'message' => 'ARENASPORTS API aktif.',
    'data' => [
        'time' => now()->toIso8601String(),
        'midtrans_configured' => filled(config('services.midtrans.server_key')),
        'whatsapp_configured' => filled(config('services.fonnte.token')),
    ],
]));

/*
|--------------------------------------------------------------------------
| AUTHENTIKASI
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/google', [AuthController::class, 'google']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/whatsapp/request', [AuthController::class, 'requestWhatsAppOtp']);
    Route::post('/whatsapp/verify', [AuthController::class, 'verifyWhatsAppOtp']);

    Route::middleware('api.token')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

/*
|--------------------------------------------------------------------------
| KATALOG PUBLIK
|--------------------------------------------------------------------------
*/

Route::get('/venues', [CatalogController::class, 'venues']);
Route::get('/venues/{venue}', [CatalogController::class, 'venueDetail']);
Route::get('/courts', [CatalogController::class, 'courts']);
Route::get('/courts/{lapangan}', [CatalogController::class, 'courtDetail']);
Route::get('/courts/{lapangan}/availability', [CatalogController::class, 'availability']);

Route::get('/chatbot/nearby-venues', [ChatbotController::class, 'nearbyVenues']);
Route::post('/chatbot', [ChatbotController::class, 'reply']);

/*
|--------------------------------------------------------------------------
| OPEN MATCH (guest boleh melihat; status join butuh token)
|--------------------------------------------------------------------------
*/

Route::middleware('api.token:optional')->group(function () {
    Route::get('/open-matches', [OpenMatchController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| ENDPOINT USER TERLOGIN
|--------------------------------------------------------------------------
*/

Route::middleware('api.token')->group(function () {
    Route::get('/home', [HomeController::class, 'index']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::match(['put', 'patch', 'post'], '/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto']);
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto']);

    // Booking
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/eligible', [BookingController::class, 'eligible']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::get('/bookings/{booking}/ticket', [BookingController::class, 'ticket']);
    Route::post('/bookings/{booking}/review', [BookingController::class, 'review']);
    Route::post('/bookings/{booking}/extension', [BookingController::class, 'extension']);

    // Pembayaran
    Route::get('/bookings/{booking}/payment', [PaymentController::class, 'checkoutBooking']);
    Route::get('/extensions/{extension}/payment', [PaymentController::class, 'checkoutExtension']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{payment}/status', [PaymentController::class, 'status']);

    // Open match
    Route::post('/open-matches', [OpenMatchController::class, 'store']);
    Route::get('/open-matches/{openMatch}', [OpenMatchController::class, 'show']);
    Route::post('/open-matches/{openMatch}/join', [OpenMatchController::class, 'join']);
    Route::get('/open-matches/{openMatch}/messages', [OpenMatchController::class, 'messages']);
    Route::post('/open-matches/{openMatch}/messages', [OpenMatchController::class, 'sendMessage']);

    // Event / turnamen resmi
    Route::post('/events/{event}/register', [OpenMatchController::class, 'registerEvent']);

    // Rewards
    Route::get('/rewards', [RewardController::class, 'index']);
    Route::post('/rewards/{reward}/redeem', [RewardController::class, 'redeem']);

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::delete('/notifications', [NotificationController::class, 'clear']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // Pengajuan venue
    Route::get('/venue-application', [VenueApplicationController::class, 'show']);
    Route::post('/venue-application', [VenueApplicationController::class, 'store']);
});
