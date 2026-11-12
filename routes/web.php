<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect(Login::redirectPathForUser(auth()->user()));
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
});

Route::post('logout', LogoutController::class)->middleware('auth')->name('logout');

