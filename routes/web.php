<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AssistanceCycleController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntitlementRuleController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TokenController;
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

    // Feature 1 – Household registration and token issuance
    Route::resource('households', HouseholdController::class)->except(['destroy']);
    Route::post('households/{household}/deactivate', [HouseholdController::class, 'deactivate'])->name('households.deactivate');
    Route::post('households/{household}/reactivate', [HouseholdController::class, 'reactivate'])->name('households.reactivate');
    Route::post('households/{household}/tokens', [TokenController::class, 'store'])->name('households.tokens.store');
    Route::post('households/{household}/tokens/reissue', [TokenController::class, 'reissue'])->name('households.tokens.reissue');
    Route::get('tokens/{token}/card', [TokenController::class, 'card'])->name('tokens.card');
    Route::post('tokens/{token}/revoke', [TokenController::class, 'revoke'])->name('tokens.revoke');
    Route::post('tokens/lookup', [TokenController::class, 'lookup'])->name('tokens.lookup');

    // Feature 2 – Assistance cycles and entitlement planning
    Route::resource('cycles', AssistanceCycleController::class)->parameters(['cycles' => 'cycle']);
    Route::get('cycles/{cycle}/preview', [AssistanceCycleController::class, 'preview'])->name('cycles.preview');
    Route::post('cycles/{cycle}/activate', [AssistanceCycleController::class, 'activate'])->name('cycles.activate');
    Route::post('cycles/{cycle}/close', [AssistanceCycleController::class, 'close'])->name('cycles.close');
    Route::post('cycles/{cycle}/rules', [EntitlementRuleController::class, 'store'])->name('cycles.rules.store');
    Route::put('cycles/{cycle}/rules/{rule}', [EntitlementRuleController::class, 'update'])->name('cycles.rules.update');
    Route::delete('cycles/{cycle}/rules/{rule}', [EntitlementRuleController::class, 'destroy'])->name('cycles.rules.destroy');

});

require __DIR__.'/auth.php';
