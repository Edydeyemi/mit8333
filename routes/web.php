<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordModificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes Orchestrator
|--------------------------------------------------------------------------
*/

// Root / Public Landing Page
Route::get('/', function () {
    return redirect()->route('login');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    // Login & 2FA
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('/login/2fa', [LoginController::class, 'showTwoFactorForm'])->name('login.2fa');
    Route::post('/login/2fa', [LoginController::class, 'verifyTwoFactor'])->name('login.2fa.verify');
    Route::post('/login/2fa/resend', [LoginController::class, 'resendTwoFactor'])->name('login.2fa.resend');

    // Registration
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

    // Password Recovery via OTP
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetOtp'])->name('password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get');

    // Password Modification (Authenticated)
    Route::post('/profile/password/send-otp', [PasswordModificationController::class, 'sendModificationOtp'])->name('profile.password.otp');
    Route::put('/profile/password', [PasswordModificationController::class, 'update'])->name('profile.password.update');

    // Central Active Notifications Endpoints
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/active', [NotificationController::class, 'active'])->name('notifications.active');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// Modular Portals Routing
Route::prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
Route::prefix('facility')->name('facility.')->group(base_path('routes/facility.php'));
Route::prefix('portal')->name('user.')->group(base_path('routes/user.php'));
