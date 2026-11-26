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
| Submenu: ganti 'route' pada item dengan 'children' => [['label' => ..., 'route' => ...], ...].
| Item induk hanya membuka/menutup submenu dan mengikuti 'permission' miliknya;
| tiap anak boleh punya 'permission' sendiri (bawaan: ikut induk).
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
            ['label' => 'Paket Layanan', 'icon' => '📦', 'route' => 'admin.paket-layanan.index', 'permission' => 'kelola master data'],
            ['label' => 'Pendaftaran', 'icon' => '📝', 'route' => 'admin.pendaftaran.index', 'permission' => 'verifikasi pendaftaran'],
            ['label' => 'Tagihan & Pembayaran', 'icon' => '💳', 'route' => 'admin.tagihan.index', 'permission' => 'kelola tagihan'],
            [
                'label' => 'Jadwal Shift', 'icon' => '🗓️', 'permission' => 'kelola jadwal',
                'children' => [
                    ['label' => 'Kelola Jadwal Shift', 'route' => 'admin.jadwal-shift.buat'],
                    ['label' => 'Lihat Jadwal Shift', 'route' => 'admin.jadwal-shift.index'],
                ],
            ],
            [
                'label' => 'Jadwal Kegiatan', 'icon' => '🧸', 'permission' => 'kelola jadwal',
                'children' => [
                    ['label' => 'Kelola Jadwal Kegiatan', 'route' => 'admin.jadwal-kegiatan.buat'],
                    ['label' => 'Lihat Jadwal Kegiatan', 'route' => 'admin.jadwal-kegiatan.index'],
                ],
            ],
            ['label' => 'Rekap Kehadiran', 'icon' => '📊', 'route' => 'admin.kehadiran.rekap', 'permission' => 'lihat semua laporan'],
            [
                'label' => 'Kelola Kelompok Usia',
                'icon' => '🧒',
                'permission' => 'kelola master data',
                'children' => [
                    [
                        'label' => 'Lihat Kelompok Usia',
                        'route' => 'admin.kelompok-usia.index',
                    ],
                    [
                        'label' => 'Tambah Kelompok Usia',
                        'route' => 'admin.kelompok-usia.buat',
                    ],
                ],
            ],
            ['label' => 'Laporan Insiden', 'icon' => '🚑', 'route' => 'admin.laporan-insiden.index', 'permission' => 'lihat semua laporan'],
            ['label' => 'Pengumuman', 'icon' => '📣', 'route' => null, 'permission' => 'kelola pengumuman'],
        ],
    ],
    [
        'group' => 'Pengasuh',
        'items' => [
            ['label' => 'Jadwal Saya', 'icon' => '🗓️', 'route' => 'pengasuh.jadwal-saya', 'permission' => 'lihat jadwal sendiri'],
            ['label' => 'Jadwal Kegiatan', 'icon' => '🧸', 'route' => 'pengasuh.jadwal-kegiatan', 'permission' => 'lihat jadwal sendiri'],
            ['label' => 'Presensi', 'icon' => '✅', 'route' => 'pengasuh.presensi', 'permission' => 'presensi anak'],
            ['label' => 'Laporan Harian', 'icon' => '📒', 'route' => 'pengasuh.laporan-harian.index', 'permission' => 'buat laporan harian'],
            ['label' => 'Laporan Insiden', 'icon' => '🚑', 'route' => 'pengasuh.laporan-insiden.index', 'permission' => 'buat laporan insiden'],
            ['label' => 'Penilaian Bulanan', 'icon' => '📈', 'route' => 'pengasuh.penilaian-bulanan.index', 'permission' => 'buat penilaian bulanan'],
            ['label' => 'Penjemput Terdaftar', 'icon' => '🚸', 'route' => 'pengasuh.penjemput.index', 'permission' => 'validasi izin penjemputan'],
            ['label' => 'Izin Penjemputan', 'icon' => '🪪', 'route' => 'pengasuh.izin-penjemputan.index', 'permission' => 'validasi izin penjemputan'],
        ],
    ],
    [
        'group' => 'Orang Tua',
        'items' => [
            ['label' => 'Paket Layanan', 'icon' => '📦', 'route' => 'orang-tua.paket-layanan.index', 'permission' => 'kelola pendaftaran sendiri'],
            ['label' => 'Pendaftaran', 'icon' => '📝', 'route' => 'orang-tua.pendaftaran.index', 'permission' => 'kelola pendaftaran sendiri'],
            ['label' => 'Data Anak', 'icon' => '🧒', 'route' => null, 'permission' => 'kelola data anak'],
            ['label' => 'Kabar Hari Ini', 'icon' => '😊', 'route' => 'orang-tua.kabar-hari-ini', 'permission' => 'lihat jadwal anak'],
            ['label' => 'Jadwal Anak', 'icon' => '🧸', 'route' => 'orang-tua.jadwal-anak', 'permission' => 'lihat jadwal anak'],
            ['label' => 'Tagihan', 'icon' => '💳', 'route' => 'orang-tua.tagihan.index', 'permission' => 'bayar tagihan'],
            ['label' => 'Penjemput', 'icon' => '🚸', 'route' => 'orang-tua.penjemput.index', 'permission' => 'kelola penjemput'],
            ['label' => 'Izin Penjemputan', 'icon' => '🪪', 'route' => 'orang-tua.izin-penjemputan.index', 'permission' => 'ajukan izin penjemputan'],
            [
                'label' => 'Laporan Anak',
                'icon' => '📒',
                'permission' => 'lihat laporan anak',
                'children' => [
                    ['label' => 'Laporan Harian', 'route' => 'orang-tua.laporan-anak.index'],
                    ['label' => 'Laporan Perkembangan Anak', 'route' => 'orang-tua.laporan-perkembangan.index'],
                ],
            ],
        ],
    ],
];