<?php

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', RegisterController::class)->name('register.store');
});

Route::middleware('auth')->group(function () {
    // Placeholder until the locations resource controller exists
    Route::view('/locations', 'locations.index')->name('locations.index');
});
