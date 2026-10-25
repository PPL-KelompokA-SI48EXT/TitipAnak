<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\FotoContoh;
use Database\Seeders\Concerns\InsertsRows;
use Illuminate\Database\Seeder;

class OperasionalSeeder extends Seeder
{
    use InsertsRows;

    public function run(): void
    {
        $uid = User::pluck('id', 'email');
        $admin = $uid['admin@titipanak.test'];
        $ortu = fn (int $n) => $uid["ortu{$n}@titipanak.test"];
        $pengasuh = fn (int $n) => $uid["pengasuh{$n}@titipanak.test"];

        $kemarin = today()->subDay();
        $hariIni = today();

        // [id, anak, tanggal, status, alasan, validasi]
        $izin = [
            [1, 1, $kemarin, 'disetujui', 'Orang tua lembur, dijemput kakak ipar.', true],
            [2, 2, $hariIni->copy()->addDay(), 'menunggu', 'Dijemput bibi karena orang tua dinas luar kota.', false],
            [3, 3, $hariIni, 'ditolak', 'Dijemput tetangga.', true],
            [4, 4, $hariIni->copy()->addDay(), 'dibatalkan', 'Rencana berubah, orang tua bisa menjemput sendiri.', false],
            [5, 5, $hariIni, 'disetujui', 'Dijemput paman karena orang tua rapat.', true],
        ];
        $nama = [1 => 'Heru Santoso', 2 => 'Lilis Wijaya', 3 => 'Tono Prasetyo', 4 => 'Yanti Gunawan', 5 => 'Agus Nugroho'];
        $rows = [];
        foreach ($izin as [$id, $anak, $tanggal, $status, $alasan, $validasi]) {
            $rows[] = [
                'id' => $id,
                'anak_id' => $anak,
                'diajukan_oleh_id' => $ortu($anak),
                'nama_penjemput' => $nama[$id],
                'path_foto_identitas' => FotoContoh::url($id + 9),
                'alasan' => $alasan,
                'tanggal_jemput' => $tanggal->toDateString(),
                'status' => $status,
                'divalidasi_oleh_id' => $validasi ? $pengasuh($anak) : null,
                'divalidasi_pada' => $validasi ? $tanggal->copy()->setTime(7, 0) : null,
            ];
        }
        $this->insertRows('izin_penjemputan', $rows);

        // Kehadiran 5 anak aktif untuk kemarin (semua sudah pulang) dan hari ini (sebagian masih di daycare).
        // Penjemput terdaftar anak A: id 2A-1 dan 2A.
        $penjemput = [1 => 'Budi Santoso', 2 => 'Andi Wijaya', 3 => 'Rizky Pratama', 4 => 'Hendra Gunawan', 5 => 'Fajar Nugroho'];
        $kondisi = ['Sehat, ceria', 'Sedikit batuk', 'Sehat, agak rewel', 'Sehat', 'Sehat, semangat'];

        $rows = [];
        $id = 1;
        foreach ([$kemarin, $hariIni] as $hari) {
            $isToday = $hari->equalTo($hariIni);
            for ($a = 1; $a <= 5; $a++) {
                $pulang = $isToday ? in_array($a, [2, 5], true) : true;
                $viaIzin = ($a === 1 && ! $isToday) || ($a === 5 && $isToday);

                $rows[] = [
                    'id' => $id++,
                    'anak_id' => $a,
                    'tanggal' => $hari->toDateString(),
                    'check_in_pada' => $hari->copy()->setTime(7, 10 + $a * 4),
                    'kondisi_check_in' => $kondisi[$a - 1],
                    'check_in_oleh_id' => $pengasuh($a),
                    'check_out_pada' => $pulang ? $hari->copy()->setTime(16, 20 + $a * 3) : null,
                    'check_out_oleh_id' => $pulang ? $pengasuh($a) : null,
                    'penjemput_terdaftar_id' => $pulang && ! $viaIzin ? 2 * $a - 1 : null,
                    'izin_penjemputan_id' => $pulang && $viaIzin ? $a : null,
                    'nama_penjemput' => $pulang ? ($viaIzin ? $nama[$a] : $penjemput[$a]) : null,
                ];
            }
        }
        $this->insertRows('kehadiran', $rows);
    }
}
