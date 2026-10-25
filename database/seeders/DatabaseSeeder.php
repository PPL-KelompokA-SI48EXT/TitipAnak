<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Urutan penting: foreign key harus sudah ada sebelum dirujuk.
     * Semua akun memakai kata sandi "password".
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PenggunaSeeder::class,
            MasterDataSeeder::class,
            PendaftaranSeeder::class,
            JadwalSeeder::class,
            OperasionalSeeder::class,
            LaporanSeeder::class,
        ]);
    }
}
