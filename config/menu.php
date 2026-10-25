<?php

/*
|--------------------------------------------------------------------------
| Menu sidebar
|--------------------------------------------------------------------------
|
| Item tampil jika pengguna punya permission Spatie yang tertulis
| (permission null = semua pengguna yang login). Item dengan route null atau
| route yang belum ada tampil nonaktif dengan label "Segera".
| Saat sebuah PBI selesai, cukup isi nama route-nya di sini.
|
*/

return [
    [
        'group' => 'Umum',
        'items' => [
            ['label' => 'Dashboard', 'icon' => '🏠', 'route' => 'dashboard', 'permission' => null],
        ],
    ],
    [
        'group' => 'Admin',
        'items' => [
            ['label' => 'Akun Pengguna', 'icon' => '👥', 'route' => null, 'permission' => 'kelola akun'],
            ['label' => 'Master Data', 'icon' => '🗂️', 'route' => null, 'permission' => 'kelola master data'],
            ['label' => 'Pendaftaran', 'icon' => '📝', 'route' => null, 'permission' => 'verifikasi pendaftaran'],
            ['label' => 'Tagihan & Pembayaran', 'icon' => '💳', 'route' => null, 'permission' => 'verifikasi pembayaran'],
            ['label' => 'Jadwal Shift', 'icon' => '🗓️', 'route' => 'admin.jadwal-shift.buat', 'permission' => 'kelola jadwal'],
            ['label' => 'Pengumuman', 'icon' => '📣', 'route' => null, 'permission' => 'kelola pengumuman'],
        ],
    ],
    [
        'group' => 'Pengasuh',
        'items' => [
            ['label' => 'Jadwal Saya', 'icon' => '🗓️', 'route' => null, 'permission' => 'lihat jadwal sendiri'],
            ['label' => 'Presensi', 'icon' => '✅', 'route' => null, 'permission' => 'presensi anak'],
            ['label' => 'Laporan Harian', 'icon' => '📒', 'route' => null, 'permission' => 'buat laporan harian'],
            ['label' => 'Laporan Insiden', 'icon' => '🚑', 'route' => null, 'permission' => 'buat laporan insiden'],
            ['label' => 'Penilaian Bulanan', 'icon' => '📈', 'route' => null, 'permission' => 'buat penilaian bulanan'],
            ['label' => 'Izin Penjemputan', 'icon' => '🪪', 'route' => null, 'permission' => 'validasi izin penjemputan'],
        ],
    ],
    [
        'group' => 'Orang Tua',
        'items' => [
            ['label' => 'Pendaftaran', 'icon' => '📝', 'route' => null, 'permission' => 'kelola pendaftaran sendiri'],
            ['label' => 'Data Anak', 'icon' => '🧒', 'route' => null, 'permission' => 'kelola data anak'],
            ['label' => 'Tagihan', 'icon' => '💳', 'route' => null, 'permission' => 'bayar tagihan'],
            ['label' => 'Penjemput', 'icon' => '🚸', 'route' => null, 'permission' => 'kelola penjemput'],
            ['label' => 'Izin Penjemputan', 'icon' => '🪪', 'route' => null, 'permission' => 'ajukan izin penjemputan'],
            ['label' => 'Laporan Anak', 'icon' => '📒', 'route' => null, 'permission' => 'lihat laporan anak'],
        ],
    ],
];
