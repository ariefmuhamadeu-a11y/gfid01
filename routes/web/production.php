<?php

use App\Http\Controllers\Production\FinishingController;
use App\Http\Controllers\Production\VendorCuttingController;
use App\Http\Controllers\Production\WipCuttingQcController;
use App\Http\Controllers\Production\WipSewingController;
use App\Http\Controllers\Production\SewingQcController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:cutting,admin'])->group(function () {

    Route::prefix('production')->name('production.')->group(function () {
        Route::prefix('vendor-cutting')->name('vendor_cutting.')
            ->group(function () {

                // ... route yang sudah ada (index, receive, showBatch) ...
                Route::get('/', [VendorCuttingController::class, 'index'])->name('index');

                Route::get('/receive/{externalTransfer}', [VendorCuttingController::class, 'receiveForm'])
                    ->name('receive.form');

                Route::post('/receive/{externalTransfer}', [VendorCuttingController::class, 'receiveStore'])
                    ->name('receive.store');

                Route::get('/batches/{batch}', [VendorCuttingController::class, 'showBatch'])
                    ->name('batches.show');

                // STEP 2: input hasil cutting per iket
                Route::get('/batches/{batch}/results', [VendorCuttingController::class, 'editResults'])
                    ->name('batches.results.edit');

                Route::post('/batches/{batch}/results', [VendorCuttingController::class, 'updateResults'])
                    ->name('batches.results.update');
                Route::post('/batches/{batch}/send-to-qc', [VendorCuttingController::class, 'sendToQc'])
                    ->name('batches.send_to_qc');

            });

        // QC Cutting (WIP)
        Route::prefix('wip-cutting-qc')
            ->name('wip_cutting_qc.')
            ->group(function () {

                Route::get('/', [WipCuttingQcController::class, 'index'])
                    ->name('index');

                Route::get('/{batch}', [WipCuttingQcController::class, 'show'])
                    ->name('show'); // 👈 ROUTE SHOW YANG BARU

                Route::get('/{batch}/edit', [WipCuttingQcController::class, 'edit'])
                    ->name('edit');

                Route::post('/{batch}', [WipCuttingQcController::class, 'update'])
                    ->name('update');
            });

        // ==========================
        // WIP SEWING
        // ==========================
        Route::prefix('wip-sewing')
            ->name('wip_sewing.')
            ->group(function () {

                // Daftar batch yang sudah qc_done & status sewing
                Route::get('/', [WipSewingController::class, 'index'])
                    ->name('index');

                // Halaman konfirmasi pembuatan Sewing Batch dari ProductionBatch
                Route::get('/create-from-batch/{batch}', [WipSewingController::class, 'createFromBatch'])
                    ->name('create_from_batch');

                // Proses simpan sewing_batch + sewing_bundle_lines (AUTO GENERATE)
                Route::post('/create-from-batch/{batch}', [WipSewingController::class, 'storeFromBatch'])
                    ->name('store_from_batch');

                // Untuk next step (lihat / edit / complete sewing)
                Route::get('/{sewingBatch}', [WipSewingController::class, 'show'])
                    ->name('show');

                Route::get('/{sewingBatch}/edit', [WipSewingController::class, 'edit'])
                    ->name('edit');

                Route::put('/{sewingBatch}', [WipSewingController::class, 'update'])
                    ->name('update');

                Route::post('/{sewingBatch}/complete', [WipSewingController::class, 'complete'])
                    ->name('complete');
            });

        // ==========================
        // QC Sewing
        // ==========================
        Route::prefix('wip-sewing-qc')
            ->name('wip_sewing_qc.')
            ->group(function () {
                Route::get('/', [SewingQcController::class, 'index'])->name('index');
                Route::get('/{bundle}', [SewingQcController::class, 'show'])->name('show');
                Route::post('/{bundle}', [SewingQcController::class, 'update'])->name('update');
            });

        // ==========================
        // FINISHING
        // ==========================

        Route::prefix('finishing')->name('finishing.')->group(function () {

            Route::get('/', [FinishingController::class, 'index'])
                ->name('index');

            Route::get('/create-from-sewing/{sewingBatch}', [FinishingController::class, 'createFromSewing'])
                ->name('create_from_sewing');

            Route::post('/create-from-sewing/{sewingBatch}', [FinishingController::class, 'storeFromSewing'])
                ->name('store_from_sewing');

            Route::get('/{finishingBatch}/edit', [FinishingController::class, 'edit'])
                ->name('edit');

            // Route::put('/{finishingBatch}', [FinishingController::class, 'update'])
            //     ->name('update');

            Route::put('/{finishingBatch}', [FinishingController::class, 'update'])->name('update');

            Route::post('/{finishing}/complete', [FinishingController::class, 'complete'])
                ->name('complete');

            Route::get('/{finishingBatch}', [FinishingController::class, 'show'])
                ->name('show');
        });

    });

});
