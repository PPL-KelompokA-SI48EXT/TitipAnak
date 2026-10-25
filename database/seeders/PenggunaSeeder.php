<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\InsertsRows;
use Illuminate\Database\Seeder;

/**
 * Semua akun memakai kata sandi: password
 */
class PenggunaSeeder extends Seeder
{
    use InsertsRows;

    public function run(): void
    {
        $admin = $this->akun('Admin Daycare', 'admin@titipanak.test', 'admin');

        $pengasuh = [
            ['Siti Rahmawati', '081210000001', 'Jl. Melati No. 12, Bandung', '2024-01-08'],
            ['Dewi Lestari', '081210000002', 'Jl. Kenanga No. 5, Bandung', '2024-03-15'],
            ['Rina Marlina', '081210000003', 'Jl. Cempaka No. 21, Bandung', '2024-06-01'],
            ['Putri Handayani', '081210000004', 'Jl. Anggrek No. 9, Bandung', '2025-01-13'],
            ['Ayu Kartika', '081210000005', 'Jl. Mawar No. 33, Bandung', '2025-04-07'],
        ];

        $orangTua = [
            ['Budi Santoso', '081310000001', 'Jl. Dago No. 101, Bandung', 'Karyawan Swasta', 'Rina Santoso - 081399990001'],
            ['Andi Wijaya', '081310000002', 'Jl. Cihampelas No. 45, Bandung', 'Dokter', 'Lisa Wijaya - 081399990002'],
            ['Rizky Pratama', '081310000003', 'Jl. Setiabudi No. 77, Bandung', 'Wiraswasta', 'Nia Pratama - 081399990003'],
            ['Hendra Gunawan', '081310000004', 'Jl. Buah Batu No. 18, Bandung', 'PNS', 'Wati Gunawan - 081399990004'],
            ['Fajar Nugroho', '081310000005', 'Jl. Riau No. 60, Bandung', 'Dosen', 'Sari Nugroho - 081399990005'],
            ['Maya Anggraini', '081310000006', 'Jl. Pasteur No. 14, Bandung', 'Desainer', 'Doni Anggraini - 081399990006'],
        ];

        $profilPengasuh = [];
        foreach ($pengasuh as $i => [$nama, $hp, $alamat, $gabung]) {
            $user = $this->akun($nama, 'pengasuh'.($i + 1).'@titipanak.test', 'pengasuh');
            $profilPengasuh[] = [
                'pengguna_id' => $user->id,
                'nama_lengkap' => $nama,
                'no_hp' => $hp,
                'alamat' => $alamat,
                'tanggal_bergabung' => $gabung,
            ];

            // 4 pengasuh pertama + admin memegang permission langsung di luar role.
            if ($i < 4) {
                $user->givePermissionTo('ekspor data');
            }
        }
        $admin->givePermissionTo('ekspor data');

        $profilOrangTua = [];
        foreach ($orangTua as $i => [$nama, $hp, $alamat, $pekerjaan, $darurat]) {
            $user = $this->akun($nama, 'ortu'.($i + 1).'@titipanak.test', 'orang_tua');
            $profilOrangTua[] = [
                'pengguna_id' => $user->id,
                'nama_lengkap' => $nama,
                'no_hp' => $hp,
                'alamat' => $alamat,
                'pekerjaan' => $pekerjaan,
                'kontak_darurat' => $darurat,
            ];
        }

        $this->insertRows('profil_pengasuh', $profilPengasuh);
        $this->insertRows('profil_orang_tua', $profilOrangTua);
    }

    private function akun(string $nama, string $email, string $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            ['nama' => $nama, 'password' => 'password', 'aktif' => true, 'terakhir_login' => now()->subHours(random_int(1, 48))],
        );

        $user->syncRoles($role);

        return $user;
    }
}
