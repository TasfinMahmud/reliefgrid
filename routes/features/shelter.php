<?php

use App\Http\Controllers\ShelterRepairController;
use Illuminate\Support\Facades\Route;

/*
| Feature 12 – Shelter repair workflow.
| Loaded inside the authenticated route group of routes/web.php (see CONTRIBUTING.md).
*/

Route::resource('repairs', ShelterRepairController::class)
    ->only(['index', 'create', 'store', 'show'])
    ->parameters(['repairs' => 'repair']);

Route::post('repairs/{repair}/assess', [ShelterRepairController::class, 'assess'])->name('repairs.assess');
Route::post('repairs/{repair}/approve', [ShelterRepairController::class, 'approve'])->name('repairs.approve');
Route::post('repairs/{repair}/reject', [ShelterRepairController::class, 'reject'])->name('repairs.reject');
Route::post('repairs/{repair}/materials/{material}/issue', [ShelterRepairController::class, 'issueMaterial'])->name('repairs.materials.issue');
Route::post('repairs/{repair}/photos', [ShelterRepairController::class, 'addPhoto'])->name('repairs.photos.store');
Route::post('repairs/{repair}/complete', [ShelterRepairController::class, 'complete'])->name('repairs.complete');
