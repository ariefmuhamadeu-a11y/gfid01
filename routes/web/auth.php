<?php

use App\Http\Controllers\UserController;

Route::middleware('auth')->group(function () {
    // ...
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
});
