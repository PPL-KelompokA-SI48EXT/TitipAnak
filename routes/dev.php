<?php

use App\Http\Controllers\Dev\AkunController;
use Illuminate\Support\Facades\Route;

// Hanya dimuat saat config('titipanak.dev_switcher') aktif (lihat bootstrap/app.php).
Route::middleware('web')->prefix('dev')->name('dev.')->group(function () {
    Route::get('akun', [AkunController::class, 'index'])->name('akun');
    Route::post('masuk/{user}', [AkunController::class, 'masuk'])->name('masuk');
    Route::post('keluar', [AkunController::class, 'keluar'])->name('keluar');
});
