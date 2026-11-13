<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\ServiceCategoryApiController;
use App\Http\Controllers\Api\V1\ServiceRequestApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    // Public Auth
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('auth.login');
    Route::post('/auth/verify-2fa', [AuthApiController::class, 'verify2fa'])->name('auth.verify_2fa');
    Route::post('/auth/forgot-password', [AuthApiController::class, 'forgotPassword'])->name('auth.forgot_password');
    Route::post('/auth/reset-password', [AuthApiController::class, 'resetPassword'])->name('auth.reset_password');

    // Public / Reference Data
    Route::get('/categories', [ServiceCategoryApiController::class, 'index'])->name('categories.index');

    // Authenticated API (Sanctum Bearer Token)
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('auth.logout');

        Route::get('/user', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'User profile retrieved.',
                'data' => auth()->user(),
            ]);
        })->name('user.profile');

        // Service Requests Lifecycle
        Route::prefix('requests')->name('requests.')->group(function (): void {
            Route::get('/', [ServiceRequestApiController::class, 'index'])->name('index');
            Route::post('/', [ServiceRequestApiController::class, 'store'])->name('store');
            Route::get('/{serviceRequest}', [ServiceRequestApiController::class, 'show'])->name('show');
            Route::post('/{serviceRequest}/assign', [ServiceRequestApiController::class, 'assign'])->name('assign');
            Route::patch('/{serviceRequest}/status', [ServiceRequestApiController::class, 'updateStatus'])->name('status');
            Route::post('/{serviceRequest}/cancel', [ServiceRequestApiController::class, 'cancel'])->name('cancel');
        });
    });
});
