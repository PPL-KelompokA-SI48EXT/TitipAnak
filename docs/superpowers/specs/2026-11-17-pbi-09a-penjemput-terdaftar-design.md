# PBI-09a — Menambahkan Orang yang Boleh Menjemput Anak

Tanggal: 2026-11-17
Status: Disetujui untuk perencanaan implementasi

## Tujuan

Sebagai orang tua, pengguna dapat mendaftarkan orang yang boleh menjemput anak tertentu dengan menyimpan nama, hubungan, nomor kontak, dan foto identitas. Data ini membantu mencegah anak dijemput oleh orang yang tidak terdaftar.

## Batasan scope

Fitur PBI-09a mencakup:

- Menambah penjemput untuk satu anak yang dipilih.
- Menampilkan daftar penjemput terdaftar milik anak-anak orang tua yang sedang login.
- Menyimpan foto identitas sebagai file private.
- Menampilkan status berhasil atau error validasi.

Fitur edit, hapus, dan menonaktifkan penjemput tidak termasuk scope PBI ini.

## Keputusan desain

### Arsitektur

Gunakan satu halaman Livewire untuk form tambah dan daftar:

- Tambahkan model `Anak` untuk membaca data anak.
- Tambahkan model `PenjemputTerdaftar` untuk tabel `penjemput_terdaftar`.
- Tambahkan `App\Livewire\OrangTua\PenjemputIndex`.
- Tambahkan view `resources/views/livewire/orang-tua/penjemput-index.blade.php`.
- Tambahkan route `orang-tua/penjemput` dengan permission `kelola penjemput`.
- Tambahkan item menu sidebar melalui konfigurasi menu yang sudah digunakan aplikasi.

Pendekatan ini mengikuti pola Livewire yang sudah dipakai pada halaman orang tua dan menghindari controller atau komponen tambahan yang belum diperlukan.

### Alur data

1. Halaman mengambil anak yang memiliki `orang_tua_id` sama dengan ID user yang sedang login.
2. Orang tua memilih satu anak.
3. Orang tua mengisi nama lengkap, hubungan, nomor HP, dan foto identitas.
4. Sistem memvalidasi semua field.
5. Sistem memastikan `anak_id` benar-benar milik user yang sedang login.
6. Foto disimpan pada disk `local` private dan path-nya disimpan pada `path_foto_identitas`.
7. Data dibuat dengan `aktif = true`.
8. Form di-reset dan pesan sukses ditampilkan.
9. Daftar memuat penjemput aktif dari anak-anak milik user yang sedang login.

### Validasi

- `anak_id`: wajib, integer, dan harus merujuk ke anak milik user login.
- `nama_lengkap`: wajib, string, maksimal 100 karakter.
- `hubungan`: wajib, string bebas, maksimal 50 karakter.
- `no_hp`: wajib, string, maksimal 20 karakter.
- `foto_identitas`: wajib, file gambar JPG/JPEG/PNG, maksimal 2 MB.

Jika user belum memiliki anak, halaman menampilkan keadaan kosong dan form tidak dapat digunakan untuk menyimpan data.

### Keamanan dan privasi

- Route berada di middleware `web` dan `auth`.
- Route memakai permission `kelola penjemput`.
- Query anak dan daftar penjemput selalu dibatasi berdasarkan `auth()->id()`.
- Validasi kepemilikan anak dilakukan di server, bukan hanya melalui pilihan pada form.
- File foto tidak dibuat menjadi URL publik pada PBI ini.

## Test yang harus tersedia

Test feature menggunakan `RefreshDatabase` dan pola `Livewire::actingAs` yang sudah ada di repo:

1. Orang tua dengan permission dapat membuka halaman.
2. Tamu ditolak oleh middleware autentikasi.
3. Role tanpa permission ditolak dengan status 403.
4. Daftar hanya menampilkan penjemput dari anak milik user login.
5. Data dan file foto berhasil disimpan dengan status aktif.
6. Field wajib yang kosong menghasilkan error dan tidak membuat data.
7. Foto non-gambar atau lebih dari 2 MB ditolak.
8. Anak milik user lain ditolak dan tidak membuat data.
9. Setelah berhasil, pesan sukses dan reset form berjalan.

## Kriteria selesai

- Orang tua dapat memilih anak miliknya dan menyimpan penjemput baru.
- Data tersimpan pada tabel `penjemput_terdaftar` dengan path foto private.
- Orang tua hanya dapat melihat data penjemput untuk anak miliknya.
- Pengguna tanpa autentikasi atau permission tidak dapat mengakses fitur.
- Semua test yang ditentukan lulus.
