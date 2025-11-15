<?php

use App\Http\Controllers\Purchasing\PurchaseController;
use App\Http\Controllers\Purchasing\PurchasePaymentController;
use Illuminate\Support\Facades\Route;

// Purchase Invoices
Route::prefix('purchasing/invoices')->name('purchasing.invoices.')->group(function () {
    Route::get('/', [PurchaseController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseController::class, 'create'])->name('create');
    Route::post('/', [PurchaseController::class, 'store'])->name('store');
    Route::get('/{invoice}', [PurchaseController::class, 'show'])->name('show');

    // ✏️ Edit & update detail baris invoice
    Route::get('/{invoice}/edit-lines', [PurchaseController::class, 'editLines'])->name('lines.edit');
    Route::put('/{invoice}/lines', [PurchaseController::class, 'updateLines'])->name('lines.update');

    // AJAX khusus purchase invoices
    Route::get('/ajax/last-price', [PurchaseController::class, 'lastPrice'])->name('ajax.last_price');
    Route::get('/ajax/history', [PurchaseController::class, 'history'])->name('ajax.history');
});

// Payment & post invoice
Route::prefix('purchasing')->name('purchasing.')->group(function () {
    Route::post('/invoices/{invoice}/payments', [PurchasePaymentController::class, 'store'])
        ->name('invoices.payments.store');

    Route::delete('/invoices/{invoice}/payments/{payment}', [PurchasePaymentController::class, 'destroy'])
        ->name('invoices.payments.destroy');

    Route::post('/invoices/{invoice}/post', [PurchaseController::class, 'post'])
        ->name('invoices.post');
});
