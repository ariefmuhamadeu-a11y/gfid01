<?php

use App\Http\Controllers\Ajax\ItemLookupController;
use Illuminate\Support\Facades\Route;

// Ajax item finished goods
Route::get('/ajax/items/finished', [ItemLookupController::class, 'searchFinished'])
    ->name('ajax.items.finished');

// NOTE:
// Ajax khusus purchase invoices tetap di routes/web/purchasing.php
// supaya masih satu konteks dengan modul purchasing.
