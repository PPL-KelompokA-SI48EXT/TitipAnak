<?php

use App\Livewire\Admin\JadwalKegiatanBuat;
use App\Livewire\Admin\JadwalKegiatanIndex;
use App\Livewire\Admin\JadwalKegiatanUbah;
use App\Livewire\Admin\JadwalShiftBuat;
use App\Livewire\Admin\RekapKehadiran;
use App\Livewire\Admin\JadwalShiftIndex;
use App\Livewire\Admin\JadwalShiftUbah;
use App\Livewire\Dashboard;
use App\Livewire\Pengasuh\CheckOut;
use App\Livewire\Pengasuh\JadwalKegiatanHarian;
use App\Livewire\Pengasuh\KehadiranHariIni;
use App\Livewire\Pengasuh\JadwalSaya;
use App\Livewire\Pengasuh\Presensi;
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
            Route::get('jadwal-shift/{jadwal}/ubah', JadwalShiftUbah::class)->name('jadwal-shift.ubah');

            Route::get('jadwal-kegiatan', JadwalKegiatanIndex::class)->name('jadwal-kegiatan.index');
            Route::get('jadwal-kegiatan/buat', JadwalKegiatanBuat::class)->name('jadwal-kegiatan.buat');
            Route::get('jadwal-kegiatan/{kegiatan}/ubah', JadwalKegiatanUbah::class)->name('jadwal-kegiatan.ubah');
        });

    Route::middleware('permission:lihat semua laporan')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('kehadiran', RekapKehadiran::class)->name('kehadiran.rekap');
        });

    Route::middleware('permission:lihat jadwal sendiri')
        ->prefix('pengasuh')
        ->name('pengasuh.')
        ->group(function () {
            Route::get('jadwal-saya', JadwalSaya::class)->name('jadwal-saya');
            Route::get('jadwal-kegiatan', JadwalKegiatanHarian::class)->name('jadwal-kegiatan');
        });

    Route::middleware('permission:presensi anak')
        ->prefix('pengasuh')
        ->name('pengasuh.')
        ->group(function () {
            Route::get('presensi', Presensi::class)->name('presensi');
            Route::get('check-out', CheckOut::class)->name('check-out');
            Route::get('kehadiran', KehadiranHariIni::class)->name('kehadiran');
        });
});
