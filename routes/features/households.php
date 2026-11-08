<?php

use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

/*
| These routes are loaded inside the authenticated route group of routes/web.php.
*/

// Feature 1 – Household registration and token issuance
Route::resource('households', HouseholdController::class)->except(['destroy']);
Route::post('households/{household}/deactivate', [HouseholdController::class, 'deactivate'])->name('households.deactivate');
Route::post('households/{household}/reactivate', [HouseholdController::class, 'reactivate'])->name('households.reactivate');
Route::post('households/{household}/tokens', [TokenController::class, 'store'])->name('households.tokens.store');
Route::post('households/{household}/tokens/reissue', [TokenController::class, 'reissue'])->name('households.tokens.reissue');
Route::get('tokens/{token}/card', [TokenController::class, 'card'])->name('tokens.card');
Route::post('tokens/{token}/revoke', [TokenController::class, 'revoke'])->name('tokens.revoke');
Route::post('tokens/lookup', [TokenController::class, 'lookup'])->name('tokens.lookup');
