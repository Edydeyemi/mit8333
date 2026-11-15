<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceRequestController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    // Executive Dashboard & Live Analytics
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [AdminDashboardController::class, 'data'])->name('dashboard.data');

    // Service Categories Management
    Route::prefix('service-categories')->name('categories.')->group(function (): void {
        Route::get('/', [ServiceCategoryController::class, 'index'])->name('index');
        Route::post('/', [ServiceCategoryController::class, 'store'])->name('store');
        Route::put('/{serviceCategory}', [ServiceCategoryController::class, 'update'])->name('update');
        Route::patch('/{serviceCategory}/toggle', [ServiceCategoryController::class, 'toggleStatus'])->name('toggle');
        Route::delete('/{serviceCategory}', [ServiceCategoryController::class, 'destroy'])->name('destroy');
    });

    // Campus Service Requests & Dispatch Command
    Route::prefix('requests')->name('requests.')->group(function (): void {
        Route::get('/', [ServiceRequestController::class, 'index'])->name('index');
        Route::get('/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('show');
        Route::post('/{serviceRequest}/assign', [ServiceRequestController::class, 'assign'])->name('assign');
    });

    // Roles & Permissions Management
    Route::prefix('roles')->name('roles.')->group(function (): void {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
    });
    Route::post('/permissions/sync', [PermissionController::class, 'sync'])->name('permissions.sync');

    // Campus Personnel & User Directory
    Route::prefix('users')->name('users.')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/toggle', [UserController::class, 'toggleStatus'])->name('toggle');
    });

    // Executive Analytics, SLA Reports & Audit Trail
    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export/requests', [ReportController::class, 'exportRequests'])->name('export.requests');
        Route::get('/export/officers', [ReportController::class, 'exportOfficers'])->name('export.officers');
        Route::get('/export/audits', [ReportController::class, 'exportAudits'])->name('export.audits');
    });

    // Notifications Center & Active Poller API
    Route::prefix('notifications')->name('notifications.')->group(function (): void {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/active', [NotificationController::class, 'active'])->name('active');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('read-all');
    });
});
