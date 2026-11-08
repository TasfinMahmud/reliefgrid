<?php

use App\Http\Controllers\StockController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
| These routes are loaded inside the authenticated route group of routes/web.php.
*/

// Feature 5 – Warehouse and stock management
Route::resource('warehouses', WarehouseController::class)->except(['destroy']);
Route::get('warehouses/{warehouse}/reconcile', [WarehouseController::class, 'reconcile'])->name('warehouses.reconcile');
Route::get('warehouses/{warehouse}/receive', [StockController::class, 'receiveForm'])->name('stock.receive');
Route::post('warehouses/{warehouse}/receive', [StockController::class, 'receive']);
Route::get('warehouses/{warehouse}/issue', [StockController::class, 'issueForm'])->name('stock.issue');
Route::post('warehouses/{warehouse}/issue', [StockController::class, 'issue']);
Route::get('stock/ledger', [StockController::class, 'ledger'])->name('stock.ledger');
Route::get('stock/expiry', [StockController::class, 'expiry'])->name('stock.expiry');
Route::get('stock/batches/{batch}', [StockController::class, 'batch'])->name('stock.batch');
Route::resource('transfers', StockTransferController::class)->only(['index', 'create', 'store', 'show'])->parameters(['transfers' => 'transfer']);
Route::post('transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->name('transfers.approve');
Route::post('transfers/{transfer}/reject', [StockTransferController::class, 'reject'])->name('transfers.reject');
Route::post('transfers/{transfer}/cancel', [StockTransferController::class, 'cancel'])->name('transfers.cancel');
