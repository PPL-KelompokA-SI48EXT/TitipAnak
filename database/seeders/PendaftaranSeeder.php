<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\FotoContoh;
use Database\Seeders\Concerns\InsertsRows;
use Illuminate\Database\Seeder;

class PendaftaranSeeder extends Seeder
{
    use InsertsRows;

    public function run(): void
    {
        $uid = User::pluck('id', 'email');
        $admin = $uid['admin@titipanak.test'];
        $ortu = fn (int $n) => $uid["ortu{$n}@titipanak.test"];

        // [id, orang tua ke-, paket, status, nama anak, JK, umur (bulan), kelompok usia]
        $data = [
            [1, 1, 1, 'disetujui', 'Aisyah Putri Santoso', 'P', 8, 1],
            [2, 2, 2, 'disetujui', 'Rafi Ahmad Wijaya', 'L', 18, 2],
            [3, 3, 1, 'disetujui', 'Nayla Zahra Pratama', 'P', 30, 3],
            [4, 4, 3, 'disetujui', 'Dimas Aditya Gunawan', 'L', 42, 4],
            [5, 5, 4, 'disetujui', 'Keisha Amelia Nugroho', 'P', 60, 5],
            [6, 6, 1, 'menunggu_verifikasi', 'Zidan Alfarizi', 'L', 20, null],
            [7, 1, 2, 'draft', 'Naufal Hakim Santoso', 'L', 34, null],
            [8, 2, 5, 'ditolak', 'Bima Satria Wijaya', 'L', 50, null],
        ];

        $pendaftaran = [];
        $anak = [];
        foreach ($data as [$id, $o, $paket, $status, $nama, $jk, $umur, $kelompok]) {
            $diajukan = $status === 'draft' ? null : now()->subDays(40 - $id);
            $diverifikasi = in_array($status, ['disetujui', 'ditolak'], true);

            $pendaftaran[] = [
                'id' => $id,
                'orang_tua_id' => $ortu($o),
                'paket_layanan_id' => $paket,
                'status' => $status,
                'diajukan_pada' => $diajukan,
                'diverifikasi_oleh_id' => $diverifikasi ? $admin : null,
                'diverifikasi_pada' => $diverifikasi ? $diajukan->copy()->addDay() : null,
                'catatan_penolakan' => $status === 'ditolak' ? 'Usia anak tidak sesuai dengan paket yang dipilih.' : null,
                'deleted_at' => null,
            ];

            $anak[] = [
                'id' => $id,
                'pendaftaran_id' => $id,
                'orang_tua_id' => $ortu($o),
                'kelompok_usia_id' => $kelompok,
                'nama_lengkap' => $nama,
                'tanggal_lahir' => today()->subMonths($umur)->toDateString(),
                'jenis_kelamin' => $jk,
                'status' => $status === 'disetujui' ? 'aktif' : 'menunggu',
                'deleted_at' => null,
            ];
        }
        $this->insertRows('pendaftaran', $pendaftaran);
        $this->insertRows('anak', $anak);

        $kesehatan = [
            [null, 'Alergi susu sapi', 'Susu sapi, keju', 'Gunakan susu formula khusus'],
            [null, 'Alergi telur', 'Telur, mayones', null],
            ['Pernah kejang demam usia 1 tahun', null, null, 'Hubungi orang tua jika suhu di atas 38°C'],
            ['Asma ringan', 'Debu', null, 'Inhaler disimpan di tas anak'],
            [null, null, 'Makanan pedas', null],
            [null, 'Alergi kacang', 'Kacang tanah', null],
            ['Imunisasi lengkap', null, null, null],
            [null, null, null, 'Belum pernah dititipkan sebelumnya'],
        ];
        $rows = [];
        foreach ($kesehatan as $i => [$medis, $alergi, $pantangan, $catatan]) {
            $rows[] = [
                'id' => $i + 1,
                'anak_id' => $i + 1,
                'riwayat_medis' => $medis,
                'alergi' => $alergi,
                'pantangan_makanan' => $pantangan,
                'catatan' => $catatan,
            ];
        }
        $this->insertRows('catatan_kesehatan', $rows);

        // 2 penjemput terdaftar untuk tiap anak aktif (id 1-5) -> id penjemput = 2*anak-1 dan 2*anak.
        $penjemput = [
            [['Budi Santoso', 'Ayah'], ['Rina Santoso', 'Ibu']],
            [['Andi Wijaya', 'Ayah'], ['Hartini Wijaya', 'Nenek']],
            [['Rizky Pratama', 'Ayah'], ['Nia Pratama', 'Ibu']],
            [['Hendra Gunawan', 'Ayah'], ['Joko Gunawan', 'Kakek']],
            [['Fajar Nugroho', 'Ayah'], ['Sari Nugroho', 'Ibu']],
        ];
        $rows = [];
        $id = 1;
        foreach ($penjemput as $anakId => $orang) {
            foreach ($orang as $k => [$nama, $hubungan]) {
                $rows[] = [
                    'id' => $id++,
                    'anak_id' => $anakId + 1,
                    'nama_lengkap' => $nama,
                    'hubungan' => $hubungan,
                    'no_hp' => '0813'.str_pad((string) ($id * 1234567 % 100000000), 8, '0', STR_PAD_LEFT),
                    'path_foto_identitas' => FotoContoh::url($id - 2),
                    'aktif' => true,
                ];
            }
        }
        $this->insertRows('penjemput_terdaftar', $rows);

        $bulan = today()->format('Ym');
        $harga = [1 => 2500000, 2 => 1500000, 3 => 2500000, 4 => 1500000, 5 => 1000000];
        // [id, pendaftaran, status]
        $tagihan = [[1, 1, 'lunas'], [2, 2, 'lunas'], [3, 3, 'lunas'], [4, 4, 'belum_lunas'], [5, 5, 'belum_lunas'], [6, 1, 'belum_lunas']];
        $rows = [];
        foreach ($tagihan as [$id, $p, $status]) {
            $terbit = now()->subDays(30 - $id);
            $rows[] = [
                'id' => $id,
                'pendaftaran_id' => $p,
                'nomor_tagihan' => "TGH-{$bulan}-".str_pad((string) $id, 4, '0', STR_PAD_LEFT),
                'jumlah' => $harga[$p],
                'status' => $status,
                'diterbitkan_pada' => $terbit,
                'jatuh_tempo' => $terbit->copy()->addDays(10)->toDateString(),
                'lunas_pada' => $status === 'lunas' ? $terbit->copy()->addDays(3) : null,
                'deleted_at' => null,
            ];
        }
        $this->insertRows('tagihan', $rows);

        // [id, tagihan, orang tua ke-, status]
        $bayar = [[1, 1, 1, 'terverifikasi'], [2, 2, 2, 'terverifikasi'], [3, 3, 3, 'terverifikasi'], [4, 4, 4, 'menunggu'], [5, 5, 5, 'ditolak'], [6, 6, 1, 'menunggu']];
        $rows = [];
        foreach ($bayar as [$id, $t, $o, $status]) {
            $diajukan = now()->subDays(26 - $id);
            $selesai = $status !== 'menunggu';
            $rows[] = [
                'id' => $id,
                'tagihan_id' => $t,
                'diajukan_oleh_id' => $ortu($o),
                'path_bukti' => FotoContoh::url($id + 14),
                'jumlah' => $harga[$tagihan[$t - 1][1]],
                'status' => $status,
                'diajukan_pada' => $diajukan,
                'diverifikasi_oleh_id' => $selesai ? $admin : null,
                'diverifikasi_pada' => $selesai ? $diajukan->copy()->addHours(6) : null,
                'catatan' => $status === 'ditolak' ? 'Bukti transfer tidak terbaca, mohon unggah ulang.' : null,
            ];
        }
        $this->insertRows('pembayaran', $rows);
    }
}
