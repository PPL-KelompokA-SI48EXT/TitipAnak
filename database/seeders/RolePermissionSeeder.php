<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $matrix = [
            'admin' => [
                'kelola akun',
                'kelola master data',
                'verifikasi pendaftaran',
                'verifikasi pembayaran',
                'kelola tagihan',
                'kelola jadwal',
                'kelola pengumuman',
                'lihat semua laporan',
                'lihat dashboard admin',
            ],
            'pengasuh' => [
                'presensi anak',
                'buat laporan harian',
                'buat laporan insiden',
                'buat penilaian bulanan',
                'validasi izin penjemputan',
                'lihat jadwal sendiri',
                'lihat catatan kesehatan',
            ],
            'orang_tua' => [
                'kelola pendaftaran sendiri',
                'kelola data anak',
                'bayar tagihan',
                'kelola penjemput',
                'ajukan izin penjemputan',
                'lihat laporan anak',
            ],
        ];

        foreach ($matrix as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            $role->syncPermissions($permissions);
        }

        // Permission yang tidak melekat pada role manapun, diberikan langsung ke pengguna tertentu.
        Permission::findOrCreate('ekspor data', 'web');
    }
}
