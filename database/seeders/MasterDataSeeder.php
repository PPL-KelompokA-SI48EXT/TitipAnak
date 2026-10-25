<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\InsertsRows;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    use InsertsRows;

    public function run(): void
    {
        $adminId = User::where('email', 'admin@titipanak.test')->value('id');
        $hariIni = today();

        $this->insertRows('kelompok_usia', [
            ['id' => 1, 'nama' => 'Bayi', 'usia_min_bulan' => 3, 'usia_maks_bulan' => 11, 'kapasitas' => 8, 'deskripsi' => 'Bayi 3 sampai 11 bulan, rasio pengasuh 1:3.'],
            ['id' => 2, 'nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12, 'deskripsi' => 'Anak mulai berjalan dan belajar bicara.'],
            ['id' => 3, 'nama' => 'Toddler', 'usia_min_bulan' => 24, 'usia_maks_bulan' => 35, 'kapasitas' => 15, 'deskripsi' => 'Eksplorasi motorik dan latihan toilet.'],
            ['id' => 4, 'nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15, 'deskripsi' => 'Bermain kelompok dan pengenalan huruf.'],
            ['id' => 5, 'nama' => 'Prasekolah', 'usia_min_bulan' => 48, 'usia_maks_bulan' => 72, 'kapasitas' => 20, 'deskripsi' => 'Persiapan masuk TK.'],
        ]);

        $this->insertRows('paket_layanan', [
            ['id' => 1, 'nama' => 'Full Day', 'deskripsi' => 'Senin sampai Jumat, 07.00 - 17.00, termasuk makan siang dan camilan.', 'harga' => 2500000, 'aktif' => true],
            ['id' => 2, 'nama' => 'Half Day Pagi', 'deskripsi' => 'Senin sampai Jumat, 07.00 - 12.00, termasuk sarapan.', 'harga' => 1500000, 'aktif' => true],
            ['id' => 3, 'nama' => 'Half Day Siang', 'deskripsi' => 'Senin sampai Jumat, 12.00 - 17.00, termasuk makan siang.', 'harga' => 1500000, 'aktif' => true],
            ['id' => 4, 'nama' => 'Weekend Care', 'deskripsi' => 'Sabtu dan Minggu, 08.00 - 16.00.', 'harga' => 1000000, 'aktif' => true],
            ['id' => 5, 'nama' => 'Fleksibel Harian', 'deskripsi' => 'Bayar per hari kunjungan, maksimal 10 hari per bulan.', 'harga' => 175000, 'aktif' => false],
        ]);

        $menu = [
            ['sarapan', ['Bubur ayam sayur', 'Roti telur', 'Nasi tim ayam', 'Oatmeal pisang', 'Nasi goreng sayur']],
            ['makan_siang', ['Nasi, ayam goreng, sup wortel', 'Nasi, ikan kukus, tumis buncis', 'Nasi, tempe orek, sayur bayam', 'Nasi, telur dadar, sayur sop', 'Nasi, daging cincang, capcay']],
            ['camilan', ['Puding susu', 'Pisang kukus', 'Biskuit dan susu', 'Bubur kacang hijau', 'Potongan buah']],
        ];
        $rows = [];
        $id = 1;
        foreach ($menu as [$waktu, $namaMenu]) {
            foreach ($namaMenu as $i => $nama) {
                $rows[] = [
                    'id' => $id++,
                    'tanggal' => $hariIni->copy()->addDays($i - 2)->toDateString(),
                    'waktu_makan' => $waktu,
                    'nama_menu' => $nama,
                    'deskripsi' => null,
                    'dibuat_oleh_id' => $adminId,
                ];
            }
        }
        $this->insertRows('menu_makan', $rows);

        $this->insertRows('pengumuman', [
            ['id' => 1, 'judul' => 'Selamat Datang di TitipAnak', 'isi' => 'Terima kasih sudah mempercayakan putra-putri Anda kepada kami. Silakan lengkapi data anak dan catatan kesehatannya.', 'status' => 'terbit', 'terbit_pada' => now()->subDays(10), 'dibuat_oleh_id' => $adminId],
            ['id' => 2, 'judul' => 'Pembukaan Pendaftaran Periode Baru', 'isi' => 'Pendaftaran anak baru dibuka sampai akhir bulan. Kuota terbatas per kelompok usia.', 'status' => 'terbit', 'terbit_pada' => now()->subDays(7), 'dibuat_oleh_id' => $adminId],
            ['id' => 3, 'judul' => 'Libur Nasional', 'isi' => 'Daycare tutup pada tanggal merah. Layanan kembali normal di hari kerja berikutnya.', 'status' => 'terbit', 'terbit_pada' => now()->subDays(3), 'dibuat_oleh_id' => $adminId],
            ['id' => 4, 'judul' => 'Jadwal Imunisasi Bulanan', 'isi' => 'Posyandu keliling akan datang ke daycare. Mohon bawa buku KIA anak.', 'status' => 'terbit', 'terbit_pada' => now()->subDay(), 'dibuat_oleh_id' => $adminId],
            ['id' => 5, 'judul' => 'Pengingat Pembayaran Tagihan', 'isi' => 'Tagihan bulan ini jatuh tempo pada tanggal 10. Unggah bukti pembayaran lewat menu Tagihan.', 'status' => 'terbit', 'terbit_pada' => now()->subHours(5), 'dibuat_oleh_id' => $adminId],
            ['id' => 6, 'judul' => 'Rencana Pentas Seni Akhir Tahun', 'isi' => 'Draf rencana kegiatan pentas seni, masih menunggu konfirmasi jadwal.', 'status' => 'draf', 'terbit_pada' => null, 'dibuat_oleh_id' => $adminId],
        ]);
    }
}
