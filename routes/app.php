<?php

use App\Livewire\Admin\JadwalShiftBuat;
use App\Livewire\Admin\JadwalShiftIndex;
use App\Livewire\Admin\PaketLayananCreate;
use App\Livewire\Admin\PaketLayananIndex;
use App\Livewire\Dashboard;
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

    Route::middleware('permission:kelola master data')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('paket-layanan', PaketLayananIndex::class)->name('paket-layanan.index');
            Route::get('paket-layanan/tambah', PaketLayananCreate::class)->name('paket-layanan.create');
        });
});
