<?php

use App\Http\Controllers\Payroll\PayrollPerPieceController;
use Illuminate\Support\Facades\Route;

Route::prefix('payroll/runs')
    ->name('payroll.runs.')
    ->group(function () {
        Route::get('/', [PayrollPerPieceController::class, 'index'])->name('index');
        Route::post('/', [PayrollPerPieceController::class, 'store'])->name('store');
        Route::get('{payrollRun}', [PayrollPerPieceController::class, 'show'])->name('show');

        Route::post('{payrollRun}/post', [PayrollPerPieceController::class, 'post'])->name('post');
    });
