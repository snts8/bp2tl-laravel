<?php

use App\Http\Controllers\Api\Admin\ChatbotFaqController;
use App\Http\Controllers\Api\Admin\TicketAdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\Superadmin\AccountController;
use App\Http\Controllers\Api\Superadmin\HistoryController;
use App\Http\Controllers\Api\Superadmin\LogController;
use App\Http\Controllers\Api\Superadmin\ResetController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/admin', [PageController::class, 'admin'])->name('admin');
Route::get('/superadmin', [PageController::class, 'superadmin'])->name('superadmin');

// URL lama (.html) tetap hidup agar bookmark / QR code lama tidak rusak.
Route::redirect('/index.html', '/', 301);
Route::redirect('/admin.html', '/admin', 301);
Route::redirect('/superadmin.html', '/superadmin', 301);

/*
|--------------------------------------------------------------------------
| API JSON (padanan folder api/*.php)
|--------------------------------------------------------------------------
| Sengaja ditaruh di routes/web.php (bukan routes/api.php) karena butuh SESI &
| perlindungan CSRF. Token CSRF dikirim frontend lewat header X-CSRF-TOKEN.
*/
Route::prefix('api')->group(function () {

    // ---- Publik ----
    Route::get('csrf-token', [AuthController::class, 'csrf']);                                   // penyegar token
    Route::post('ajukan', [TicketController::class, 'store'])->middleware('throttle:ajukan');     // ajukan.php
    Route::get('lacak', [TicketController::class, 'show'])->middleware('throttle:lacak');        // lacak.php
    Route::post('chatbot', [ChatbotController::class, 'ask'])->middleware('throttle:chatbot');   // chatbot.php
    Route::get('settings', [SettingsController::class, 'index']);                                // settings_get.php

    // ---- Sesi (realm = ?realm=admin|superadmin) ----
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');        // login.php
    Route::post('logout', [AuthController::class, 'logout']);                                    // logout.php
    Route::get('session', [AuthController::class, 'session']);                                   // session_check.php

    // ---- Admin ----
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('tiket', [TicketAdminController::class, 'index']);                            // list.php
        Route::post('jawab', [TicketAdminController::class, 'jawab']);                           // jawab.php
        Route::post('hapus', [TicketAdminController::class, 'hapus']);                           // hapus.php
        Route::post('settings', [SettingsController::class, 'save']);                            // settings_save.php
        Route::get('chatbot-faq', [ChatbotFaqController::class, 'index']);                       // chatbot_faq.php (GET)
        Route::post('chatbot-faq', [ChatbotFaqController::class, 'handle']);                     // chatbot_faq.php (POST)
    });

    // ---- Super Admin ----
    Route::middleware('role:superadmin')->prefix('superadmin')->group(function () {
        Route::get('log', [LogController::class, 'index']);                                      // superadmin_log.php
        Route::get('history', [HistoryController::class, 'index']);                              // superadmin_history.php
        Route::get('accounts', [AccountController::class, 'index']);                             // superadmin_accounts.php (GET)
        Route::post('accounts', [AccountController::class, 'handle']);                           // superadmin_accounts.php (POST)
        Route::post('reset', [ResetController::class, 'handle']);                                // superadmin_reset.php
    });
});
