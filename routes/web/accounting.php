<?php

use App\Http\Controllers\Accounting\JournalController;
use Illuminate\Support\Facades\Route;

Route::prefix('accounting')->name('accounting.')->group(function () {
    Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('/journals/create', [JournalController::class, 'create'])->name('journals.create');
    Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
    Route::get('/journals/{id}', [JournalController::class, 'show'])->name('journals.show');

    // Buku Besar (ledger)
    Route::get('/ledger', [JournalController::class, 'ledger'])->name('ledger');
});
