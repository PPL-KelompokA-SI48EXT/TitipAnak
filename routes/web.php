<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SeederDataController;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::get('/data-seeder', SeederDataController::class)->name('data-seeder');

Route::get('/', LandingController::class)->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
});

Route::post('logout', LogoutController::class)->middleware('auth')->name('logout');

