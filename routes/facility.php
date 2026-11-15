<?php

declare(strict_types=1);

use App\Http\Controllers\Facility\FacilityDashboardController;
use App\Http\Controllers\Facility\FacilityTaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    // Facility Technician Dashboard Desk & Live Poller
    Route::get('/', [FacilityDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [FacilityDashboardController::class, 'data'])->name('dashboard.data');

    // Field Tasks & Kanban Pipeline
    Route::prefix('tasks')->name('tasks.')->group(function (): void {
        Route::get('/', [FacilityTaskController::class, 'index'])->name('index');
        Route::get('/{serviceRequest}', [FacilityTaskController::class, 'show'])->name('show');
        Route::post('/{serviceRequest}/status', [FacilityTaskController::class, 'updateStatus'])->name('status');
    });
});
