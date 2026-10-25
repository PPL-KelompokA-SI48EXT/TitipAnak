<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dev account switcher
    |--------------------------------------------------------------------------
    |
    | Menu "Sebagai [peran]" di navbar untuk berpindah akun tanpa kata sandi.
    | HANYA untuk pengembangan. Bawaan: aktif hanya saat APP_ENV=local.
    | Jangan pernah mengaktifkannya di produksi.
    |
    */
    'dev_switcher' => (bool) env('DEV_ACCOUNT_SWITCHER', env('APP_ENV') === 'local'),

    /*
    |--------------------------------------------------------------------------
    | Peran (nama role Spatie) beserta tampilannya
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'admin' => ['label' => 'Admin', 'badge' => 'bg-yellow'],
        'pengasuh' => ['label' => 'Pengasuh', 'badge' => 'bg-blue'],
        'orang_tua' => ['label' => 'Orang Tua', 'badge' => 'bg-mint'],
    ],

];
