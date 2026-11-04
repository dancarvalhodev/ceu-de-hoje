<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', RegisterController::class)->name('register.store');

    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', LoginController::class)->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    // Placeholder until the locations resource controller exists
    Route::view('/locations', 'locations.index')->name('locations.index');
});
