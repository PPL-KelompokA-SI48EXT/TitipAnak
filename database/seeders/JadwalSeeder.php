<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\InsertsRows;
use Illuminate\Database\Seeder;

class JadwalSeeder extends Seeder
{
    use InsertsRows;

    public function run(): void
    {
        $uid = User::pluck('id', 'email');

        // Pengasuh ke-N memegang kelompok usia ke-N, Senin sampai Jumat.
        $shift = [];
        $id = 1;
        for ($p = 1; $p <= 5; $p++) {
            for ($hari = 1; $hari <= 5; $hari++) {
                $shift[] = [
                    'id' => $id++,
                    'pengasuh_id' => $uid["pengasuh{$p}@titipanak.test"],
                    'kelompok_usia_id' => $p,
                    'hari' => $hari,
                    'jam_mulai' => '07:00:00',
                    'jam_selesai' => '15:00:00',
                ];
            }
        }
        $this->insertRows('jadwal_shift', $shift);

        $kegiatan = [
            ['Sarapan', 'makan', '07:30:00', '08:00:00'],
            ['Bermain bebas', 'bermain', '08:00:00', '09:30:00'],
            ['Belajar sambil bermain', 'belajar', '09:30:00', '10:30:00'],
            ['Makan siang', 'makan', '11:30:00', '12:15:00'],
            ['Tidur siang', 'tidur', '12:30:00', '14:30:00'],
        ];
        $rows = [];
        $id = 1;
        for ($kelompok = 1; $kelompok <= 5; $kelompok++) {
            foreach ($kegiatan as [$nama, $jenis, $mulai, $selesai]) {
                $rows[] = [
                    'id' => $id++,
                    'kelompok_usia_id' => $kelompok,
                    'nama_kegiatan' => $nama,
                    'jenis_kegiatan' => $jenis,
                    'hari' => null,
                    'jam_mulai' => $mulai,
                    'jam_selesai' => $selesai,
                    'catatan' => null,
                ];
            }
        }
        $this->insertRows('jadwal_kegiatan', $rows);
    }
}
