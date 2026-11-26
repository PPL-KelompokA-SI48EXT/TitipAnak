<?php

use App\Livewire\Admin\JadwalKegiatanBuat;
use App\Livewire\Admin\JadwalKegiatanIndex;
use App\Livewire\Admin\JadwalKegiatanUbah;
use App\Livewire\Admin\JadwalShiftBuat;
use App\Livewire\Admin\JadwalShiftIndex;
use App\Livewire\Admin\JadwalShiftUbah;
use App\Livewire\Admin\KelompokUsiaBuat;
use App\Livewire\Admin\KelompokUsiaIndex;
use App\Livewire\Admin\PaketLayananCreate;
use App\Livewire\Admin\PaketLayananIndex;
use App\Livewire\Admin\PendaftaranDetail as PendaftaranDetailAdmin;
use App\Livewire\Admin\PendaftaranIndex as PendaftaranIndexAdmin;
use App\Livewire\Admin\RekapKehadiran;
use App\Livewire\Admin\TagihanIndex;
use App\Livewire\Dashboard;
use App\Livewire\OrangTua\JadwalAnak;
use App\Livewire\OrangTua\KabarHariIni;
use App\Livewire\OrangTua\PaketLayananIndex as PaketLayananIndexOrangTua;
use App\Livewire\OrangTua\PendaftaranBuat;
use App\Livewire\OrangTua\PendaftaranDetail;
use App\Livewire\OrangTua\PendaftaranIndex;
use App\Livewire\OrangTua\IzinPenjemputanIndex as IzinPenjemputanIndexOrangTua;
use App\Livewire\OrangTua\PenjemputIndex;
use App\Livewire\OrangTua\TagihanIndex as TagihanIndexOrangTua;
use App\Livewire\Pengasuh\JadwalKegiatanHarian;
use App\Livewire\Pengasuh\JadwalSaya;
use App\Livewire\Pengasuh\IzinPenjemputanIndex as IzinPenjemputanIndexPengasuh;
use App\Livewire\Pengasuh\PenjemputIndex as PenjemputIndexPengasuh;
use App\Livewire\Pengasuh\Presensi;
use App\Livewire\Pengasuh\LaporanHarianIndex;
use App\Livewire\OrangTua\LaporanAnakIndex;
use App\Livewire\Pengasuh\LaporanInsidenIndex;
use App\Livewire\Admin\LaporanInsidenIndex as AdminLaporanInsidenIndex;
use App\Livewire\Pengasuh\PenilaianBulananIndex;
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

    Route::middleware('permission:kelola master data')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('paket-layanan', PaketLayananIndex::class)->name('paket-layanan.index');
            Route::get('paket-layanan/tambah', PaketLayananCreate::class)->name('paket-layanan.create');
        });

    Route::middleware('permission:verifikasi pendaftaran')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('pendaftaran', PendaftaranIndexAdmin::class)->name('pendaftaran.index');
            Route::get('pendaftaran/{pendaftaran}', PendaftaranDetailAdmin::class)->whereNumber('pendaftaran')->name('pendaftaran.detail');
        });

    Route::middleware('permission:kelola tagihan')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('tagihan', TagihanIndex::class)->name('tagihan.index');
        });

    Route::middleware('permission:validasi izin penjemputan')
        ->prefix('pengasuh')
        ->name('pengasuh.')
        ->group(function () {
            Route::get('penjemput', PenjemputIndexPengasuh::class)->name('penjemput.index');
            Route::get('izin-penjemputan', IzinPenjemputanIndexPengasuh::class)->name('izin-penjemputan.index');
        });

    Route::middleware('permission:kelola pendaftaran sendiri')
        ->prefix('orang-tua')
        ->name('orang-tua.')
        ->group(function () {
            Route::get('paket-layanan', PaketLayananIndexOrangTua::class)->name('paket-layanan.index');
            Route::get('pendaftaran', PendaftaranIndex::class)->name('pendaftaran.index');
            Route::get('pendaftaran/buat', PendaftaranBuat::class)->name('pendaftaran.buat');
            Route::get('pendaftaran/{pendaftaran}', PendaftaranDetail::class)->whereNumber('pendaftaran')->name('pendaftaran.detail');
        });

    Route::middleware('permission:kelola penjemput')
        ->prefix('orang-tua')
        ->name('orang-tua.')
        ->group(function () {
            Route::get('penjemput', PenjemputIndex::class)->name('penjemput.index');
        });

    Route::middleware('permission:ajukan izin penjemputan')
        ->prefix('orang-tua')
        ->name('orang-tua.')
        ->group(function () {
            Route::get('izin-penjemputan', IzinPenjemputanIndexOrangTua::class)->name('izin-penjemputan.index');
        });

    Route::middleware('permission:bayar tagihan')
        ->prefix('orang-tua')
        ->name('orang-tua.')
        ->group(function () {
            Route::get('tagihan', TagihanIndexOrangTua::class)->name('tagihan.index');
        });

    Route::middleware('permission:lihat jadwal anak')
        ->prefix('orang-tua')
        ->name('orang-tua.')
        ->group(function () {
            Route::get('kabar-hari-ini', KabarHariIni::class)->name('kabar-hari-ini');
            Route::get('jadwal-anak', JadwalAnak::class)->name('jadwal-anak');
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
            // Check-out dan daftar kehadiran sekarang menyatu di halaman Presensi; tautan lama dialihkan.
            Route::redirect('check-out', '/pengasuh/presensi')->name('check-out');
            Route::redirect('kehadiran', '/pengasuh/presensi')->name('kehadiran');
        });

    Route::middleware('permission:kelola master data')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('kelompok-usia', KelompokUsiaIndex::class)->name('kelompok-usia.index');
            Route::get('kelompok-usia/buat', KelompokUsiaBuat::class)->name('kelompok-usia.buat');
        });
    Route::middleware('permission:buat laporan harian')
        ->prefix('pengasuh')
        ->name('pengasuh.')
        ->group(function () {
            Route::get('laporan-harian', LaporanHarianIndex::class)->name('laporan-harian.index');
        });
    Route::middleware('permission:lihat laporan anak')
        ->prefix('orang-tua')
        ->name('orang-tua.')
        ->group(function () {
            Route::get('laporan-anak', LaporanAnakIndex::class)->name('laporan-anak.index');
        });
    Route::middleware('permission:buat laporan insiden')
        ->prefix('pengasuh')
        ->name('pengasuh.')
        ->group(function () {
            Route::get('laporan-insiden', LaporanInsidenIndex::class)->name('laporan-insiden.index');
        });
    Route::middleware('permission:lihat semua laporan')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('laporan-insiden', AdminLaporanInsidenIndex::class)->name('laporan-insiden.index');
        });
    Route::middleware('permission:buat penilaian bulanan')
        ->prefix('pengasuh')
        ->name('pengasuh.')
        ->group(function () {
            Route::get('penilaian-bulanan', PenilaianBulananIndex::class)->name('penilaian-bulanan.index');
        });
});
