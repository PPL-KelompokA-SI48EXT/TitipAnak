<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

// Halaman aplikasi yang membutuhkan login. Tambahkan route PBI baru di grup yang sesuai.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
});