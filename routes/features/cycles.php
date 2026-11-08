<?php

use App\Http\Controllers\AssistanceCycleController;
use App\Http\Controllers\EntitlementRuleController;
use Illuminate\Support\Facades\Route;

/*
| These routes are loaded inside the authenticated route group of routes/web.php.
*/

// Feature 2 – Assistance cycles and entitlement planning
Route::resource('cycles', AssistanceCycleController::class)->parameters(['cycles' => 'cycle']);
Route::get('cycles/{cycle}/preview', [AssistanceCycleController::class, 'preview'])->name('cycles.preview');
Route::post('cycles/{cycle}/activate', [AssistanceCycleController::class, 'activate'])->name('cycles.activate');
Route::post('cycles/{cycle}/close', [AssistanceCycleController::class, 'close'])->name('cycles.close');
Route::post('cycles/{cycle}/rules', [EntitlementRuleController::class, 'store'])->name('cycles.rules.store');
Route::put('cycles/{cycle}/rules/{rule}', [EntitlementRuleController::class, 'update'])->name('cycles.rules.update');
Route::delete('cycles/{cycle}/rules/{rule}', [EntitlementRuleController::class, 'destroy'])->name('cycles.rules.destroy');
