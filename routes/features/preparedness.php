<?php

use App\Http\Controllers\EvacuationSiteController;
use App\Http\Controllers\HazardAlertController;
use App\Http\Controllers\IncidentController;
use Illuminate\Support\Facades\Route;

/*
| These routes are loaded inside the authenticated route group of routes/web.php.
*/

// Feature 14 – Preparedness and incident response
Route::resource('alerts', HazardAlertController::class)->only(['index', 'create', 'store', 'show'])->parameters(['alerts' => 'alert']);
Route::post('alerts/{alert}/cancel', [HazardAlertController::class, 'cancel'])->name('alerts.cancel');
Route::resource('sites', EvacuationSiteController::class)->except(['destroy'])->parameters(['sites' => 'site']);
Route::post('sites/{site}/occupancy', [EvacuationSiteController::class, 'occupancy'])->name('sites.occupancy');
Route::post('sites/{site}/toggle-open', [EvacuationSiteController::class, 'toggleOpen'])->name('sites.toggle-open');
Route::resource('incidents', IncidentController::class)->only(['index', 'create', 'store', 'show']);
Route::post('incidents/{incident}/investigate', [IncidentController::class, 'investigate'])->name('incidents.investigate');
Route::post('incidents/{incident}/respond', [IncidentController::class, 'respond'])->name('incidents.respond');
Route::post('incidents/{incident}/note', [IncidentController::class, 'note'])->name('incidents.note');
Route::post('incidents/{incident}/close', [IncidentController::class, 'close'])->name('incidents.close');
