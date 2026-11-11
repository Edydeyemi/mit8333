<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    // Executive Dashboard
    Route::get('/', function () {
        return view('admin.admin_dashboard', [
            'user' => auth()->user(),
        ]);
    })->name('dashboard');

    // Service Categories Management
    Route::prefix('service-categories')->name('categories.')->group(function (): void {
        Route::get('/', [ServiceCategoryController::class, 'index'])->name('index');
        Route::post('/', [ServiceCategoryController::class, 'store'])->name('store');
        Route::put('/{serviceCategory}', [ServiceCategoryController::class, 'update'])->name('update');
        Route::patch('/{serviceCategory}/toggle', [ServiceCategoryController::class, 'toggleStatus'])->name('toggle');
        Route::delete('/{serviceCategory}', [ServiceCategoryController::class, 'destroy'])->name('destroy');
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

    // Notifications Center & Active Poller API
    Route::prefix('notifications')->name('notifications.')->group(function (): void {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/active', [NotificationController::class, 'active'])->name('active');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('read-all');
    });
});
