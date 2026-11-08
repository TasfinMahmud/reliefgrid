<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Administration
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });

    // Reference data
    Route::resource('blocks', BlockController::class)->except(['show', 'destroy']);
    Route::resource('items', ItemController::class)->except(['show', 'destroy']);

    // Feature routes: every feature registers its own routes in routes/features/<feature>.php
    // (loaded here so they inherit the auth + active middleware).
    foreach (glob(__DIR__.'/features/*.php') as $featureRoutes) {
        require $featureRoutes;
    }
});

require __DIR__.'/auth.php';
