<?php

declare(strict_types=1);

use App\Http\Controllers\User\ServiceRequestController;
use App\Http\Controllers\User\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    // Resident / Student Dashboard Overview & Live Poller
    Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [StudentDashboardController::class, 'data'])->name('dashboard.data');

    // Service Requests Lifecycle
    Route::get('/requests', [ServiceRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [ServiceRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [ServiceRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{serviceRequest}/edit', [ServiceRequestController::class, 'edit'])->name('requests.edit');
    Route::put('/requests/{serviceRequest}', [ServiceRequestController::class, 'update'])->name('requests.update');
    Route::post('/requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancel'])->name('requests.cancel');
});
