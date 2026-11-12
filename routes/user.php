<?php

declare(strict_types=1);

use App\Http\Controllers\User\ServiceRequestController;
use App\Http\Controllers\User\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    // Resident / Student Dashboard Overview
    Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');

    // Service Requests Lifecycle
    Route::get('/requests', [ServiceRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [ServiceRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [ServiceRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
    Route::post('/requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancel'])->name('requests.cancel');
});
