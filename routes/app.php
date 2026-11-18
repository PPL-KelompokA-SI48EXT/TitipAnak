<?php

use App\Livewire\Admin\JadwalShiftBuat;
use App\Livewire\Admin\JadwalShiftIndex;
use App\Livewire\Dashboard;
use App\Livewire\OrangTua\PendaftaranBuat;
use App\Livewire\OrangTua\PendaftaranIndex;
use Illuminate\Support\Facades\Route;

// Halaman aplikasi yang membutuhkan login. Tambahkan route PBI baru di grup yang sesuai.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::middleware('permission:kelola jadwal')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('jadwal-shift', JadwalShiftIndex::class)->name('jadwal-shift.index');
            Route::get('jadwal-shift/buat', JadwalShiftBuat::class)->name('jadwal-shift.buat');
        });

    Route::middleware('permission:kelola pendaftaran sendiri')
        ->prefix('orang-tua')
        ->name('orang-tua.')
        ->group(function () {
            Route::get('pendaftaran', PendaftaranIndex::class)->name('pendaftaran.index');
            Route::get('pendaftaran/buat', PendaftaranBuat::class)->name('pendaftaran.buat');
        });
});
