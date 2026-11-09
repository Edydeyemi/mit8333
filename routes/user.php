<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return view('frontend.dashboard', [
            'user' => auth()->user(),
        ]);
    })->name('dashboard');
});

