<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\FotoContoh;
use Database\Seeders\Concerns\InsertsRows;
use Illuminate\Database\Seeder;

class LaporanSeeder extends Seeder
{
    use InsertsRows;

    public function run(): void
    {
        $uid = User::pluck('id', 'email');
        $pengasuh = fn (int $n) => $uid["pengasuh{$n}@titipanak.test"];

        $hari = [today()->subDay(), today()];
        $status = ['baik', 'sebagian', 'baik', 'baik', 'tidak_makan'];
        $mood = ['ceria', 'tenang', 'rewel', 'ceria', 'bersemangat'];
        $aktivitas = [
            'Tummy time dan bermain bola lembut.',
            'Belajar berjalan di taman bermain.',
            'Menyusun balok dan menggambar.',
            'Bermain peran dan bernyanyi bersama.',
            'Mengenal huruf dan mewarnai.',
        ];

        // Laporan harian hanya untuk anak yang check-in hari itu.
        $rows = [];
        $id = 1;
        foreach ($hari as $tanggal) {
            for ($a = 1; $a <= 5; $a++) {
                $rows[] = [
                    'id' => $id++,
                    'anak_id' => $a,
                    'pengasuh_id' => $pengasuh($a),
                    'tanggal' => $tanggal->toDateString(),
                    'status_makan' => $status[$a - 1],
                    'catatan_makan' => $status[$a - 1] === 'tidak_makan' ? 'Menolak makan siang, hanya minum susu.' : 'Makan siang dihabiskan.',
                    'durasi_tidur_menit' => 60 + $a * 15,
                    'catatan_buang_air' => 'BAK '.(2 + $a % 3).' kali, BAB 1 kali.',
                    'suasana_hati' => $mood[$a - 1],
                    'catatan_aktivitas' => $aktivitas[$a - 1],
                    'catatan_umum' => null,
                ];
            }
        }
        $this->insertRows('laporan_harian', $rows);

        // Foto kegiatan untuk laporan hari ini (id 6-10).
        $rows = [];
        for ($i = 1; $i <= 5; $i++) {
            $rows[] = [
                'id' => $i,
                'laporan_harian_id' => 5 + $i,
                'path_file' => FotoContoh::url($i + 4),
                'tipe_mime' => 'image/png',
                'ukuran_file' => 180000 + $i * 21500,
                'keterangan' => 'Kegiatan '.strtolower($aktivitas[$i - 1]),
            ];
        }
        $this->insertRows('lampiran_laporan', $rows);

        $insiden = [
            [2, 'Terjatuh saat berjalan', 'Rafi terpeleset di area bermain dan lututnya lecet.', 'Lutut dibersihkan dan diberi plester, orang tua dikabari.'],
            [3, 'Tergigit teman', 'Nayla tergigit teman saat berebut mainan.', 'Area digigit dikompres dingin, kedua anak didamaikan.'],
            [4, 'Demam ringan', 'Dimas demam 37,9°C setelah tidur siang.', 'Diberi minum, suhu dipantau, orang tua dihubungi.'],
            [1, 'Muntah setelah minum susu', 'Aisyah muntah sedikit setelah minum susu.', 'Posisi disendawakan, diganti pakaian, kondisi dipantau.'],
            [5, 'Jari terjepit pintu', 'Keisha jarinya terjepit pintu lemari mainan.', 'Dikompres es, tidak ada luka terbuka, orang tua diberi tahu.'],
        ];
        $rows = [];
        foreach ($insiden as $i => [$anak, $judul, $deskripsi, $tindakan]) {
            $rows[] = [
                'id' => $i + 1,
                'anak_id' => $anak,
                'pengasuh_id' => $pengasuh($anak),
                'waktu_kejadian' => now()->subDays(5 - $i)->setTime(10 + $i, 15),
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'tindakan' => $tindakan,
            ];
        }
        $this->insertRows('laporan_insiden', $rows);

        // Penilaian bulanan: bulan lalu dan 2 bulan lalu untuk tiap anak aktif.
        $rows = [];
        $id = 1;
        foreach ([1, 2] as $mundur) {
            $periode = today()->startOfMonth()->subMonths($mundur);
            for ($a = 1; $a <= 5; $a++) {
                $rows[] = [
                    'id' => $id++,
                    'anak_id' => $a,
                    'pengasuh_id' => $pengasuh($a),
                    'periode_bulan' => $periode->toDateString(),
                    'catatan_motorik' => 'Perkembangan motorik sesuai usia dan semakin aktif.',
                    'catatan_bahasa' => 'Kosakata bertambah, mulai merespons instruksi sederhana.',
                    'catatan_sosial' => 'Mulai mau berbagi mainan dan bermain bersama teman.',
                    'catatan_kognitif' => 'Antusias mengenal warna, bentuk, dan bunyi.',
                    'catatan_umum' => $mundur === 1 ? 'Perkembangan baik, pertahankan rutinitas.' : null,
                ];
            }
        }
        $this->insertRows('laporan_perkembangan', $rows);
    }
}
