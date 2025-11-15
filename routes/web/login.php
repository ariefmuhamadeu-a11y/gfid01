<?php

use App\Http\Controllers\Auth\LoginController;

// HALAMAN LOGIN
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('guest');

// PROSES LOGIN
Route::post('/login', [LoginController::class, 'login'])
    ->name('login.submit')
    ->middleware('guest');

// LOGOUT
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');
