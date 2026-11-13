<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordModificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes Orchestrator
|--------------------------------------------------------------------------
*/

// Root / Public Landing Page
Route::get('/', function () {
    if (Auth::check()) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return match ($user->role) {
            'super_admin', 'admin', 'data_entry' => redirect()->route('admin.dashboard'),
            'officer' => redirect()->route('facility.dashboard'),
            default => redirect()->route('user.dashboard'),
        };
    }

    return view('frontend.landing');
})->name('landing');

// API Documentation & Schema Routes
Route::get('/docs', function () {
    return view('docs.api_docs');
})->name('api.docs');

Route::get('/docs/openapi.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'text/yaml',
    ]);
})->name('api.docs.openapi');

Route::get('/docs/postman_collection.json', function () {
    return response()->file(base_path('docs/postman_collection.json'), [
        'Content-Type' => 'application/json',
    ]);
})->name('api.docs.postman');

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

    // Profile Photo Management
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');

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
