<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public Auth
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('auth.login');
    Route::post('/auth/verify-2fa', [AuthApiController::class, 'verify2fa'])->name('auth.verify_2fa');
    Route::post('/auth/forgot-password', [AuthApiController::class, 'forgotPassword'])->name('auth.forgot_password');
    Route::post('/auth/reset-password', [AuthApiController::class, 'resetPassword'])->name('auth.reset_password');

    // Authenticated API
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('auth.logout');
        Route::get('/user', function () {
            return response()->json([
                'status' => 'success',
                'data' => auth()->user(),
            ]);
        })->name('user.profile');
    });
});

