# Skenario Demo PBI-14, PBI-15, PBI-16

Siapkan data: `php artisan migrate:fresh --seed`. Semua akun memakai kata sandi `password`
(atau pakai pemilih akun di `/dev/akun` saat mode pengembangan aktif).
Seeder mengisi kehadiran kemarin dan hari ini serta izin penjemputan, jadi demo bisa langsung jalan.

| Peran | Akun |
|---|---|
| Admin | `admin@titipanak.test` |
| Pengasuh | `pengasuh1@titipanak.test` … `pengasuh5@titipanak.test` |
| Orang tua | `ortu1@titipanak.test` … |

## 1. Admin mengatur jadwal (PBI-14)
1. Masuk sebagai Admin, buka **Jadwal Kegiatan**. Filter berdasarkan kelompok usia, hari, dan jenis.
2. **Buat kegiatan baru**: pilih preset, isi jam. Coba jam yang bertabrakan dengan kegiatan lain untuk melihat pesan bentrok.
3. **Ubah** kegiatan, lalu **Hapus** dengan dialog konfirmasi.
4. Buka **Jadwal Shift**: ubah atau hapus shift pengasuh.

## 2. Pengasuh melihat jadwal sendiri (PBI-14)
1. Masuk sebagai `pengasuh1`. Buka **Jadwal Saya** (pola mingguan) dan **Jadwal Kegiatan** (hari ini, kegiatan berlangsung ditandai).

## 3. Presensi anak (PBI-15)
1. Masih sebagai pengasuh, buka **Presensi, lalu Check-in**. Catat kedatangan anak dengan kondisi. Anak yang sudah check-in tidak bisa dicatat dua kali.
2. Buka **Kehadiran Hari Ini**: status belum datang, hadir, dan pulang beserta hitungannya.

## 4. Check-out dengan pencocokan penjemput (PBI-16)
1. Buka **Presensi, lalu Check-out**, pilih anak yang masih di tempat.
2. Pilih penjemput terdaftar: check-out berhasil.
3. Pilih penjemput lain dan ketik nama yang tidak terdaftar: ditolak, anak tidak dilepas, muncul panduan dan kontak orang tua.
4. Anak dengan izin penjemputan yang disetujui hari ini: izin muncul sebagai pilihan dan dapat dipakai sekali.

## 5. Rekap dan penghapusan (PBI-16)
1. Masuk sebagai Admin, buka **Rekap Kehadiran**. Filter tanggal, kelompok, dan nama. Ganti tampilan ke **Ringkasan per anak**.
2. **Hapus** satu catatan: wajib alasan minimal 5 karakter. Penghapusan tercatat di tabel `log_penghapusan_kehadiran`.

## 6. Orang tua melihat jadwal anak (PBI-15f)
1. Masuk sebagai orang tua (misalnya `ortu1`), buka **Jadwal Anak**.
2. Untuk setiap anak aktif tampil **pengasuh yang menjaga hari ini** (nama dan jam shift) dan **kegiatan harian** kelompok usianya, berurutan menurut jam. Kegiatan yang sedang berlangsung ditandai.
3. Halaman hanya baca. Orang tua hanya melihat anaknya sendiri.

Catatan: setelah menarik kode ini, jalankan `php artisan db:seed --class=RolePermissionSeeder` (atau `migrate:fresh --seed`) agar permission `lihat jadwal anak` terdaftar.
