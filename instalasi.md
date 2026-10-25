# Instalasi TitipAnak

Stack: Laravel 13, Livewire 4, Spatie Laravel Permission.

## Kebutuhan

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm

## Langkah

```bash
git clone https://github.com/PPL-KelompokA-SI48EXT/TitipAnak.git
cd TitipAnak
git checkout -b nama-kamu    # branch masing-masing
composer install
npm install
copy .env.example .env       # macOS/Linux: cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Buka `http://localhost:8000`.

## Database

Default memakai SQLite, jadi tidak perlu install apa pun. Kalau file `database/database.sqlite` belum ada, artisan biasanya menawarkan untuk membuatnya. Kalau tidak, buat manual:

```bash
touch database/database.sqlite                # macOS/Linux
New-Item database/database.sqlite             # PowerShell
```

Untuk memakai MySQL 8, buat database kosong lalu ubah `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=titipanak
DB_USERNAME=root
DB_PASSWORD=
```

## Migration

`php artisan migrate` membuat semua tabel sekaligus: `pengguna`, tabel Spatie Permission, dan tabel domain (pendaftaran, anak, tagihan, kehadiran, laporan, dan lainnya).

Tabel akun bernama `pengguna` (bukan `users`). Kalau sebelumnya sudah migrate dengan skema lama, reset database lokal dengan:

```bash
php artisan migrate:fresh
```

Perintah ini menghapus seluruh data lokal.

### Menambah migration baru

- Buat lewat `php artisan make:migration` supaya timestamp otomatis dan urutannya benar. Jangan menulis nama file migration secara manual.
- Migration yang punya foreign key harus lebih baru daripada migration tabel yang dirujuk.
- Jangan mengubah migration yang sudah ada di `main`. Buat migration baru untuk perubahan skema.

## Seeder (data awal)

`php artisan migrate --seed` mengisi semua tabel dengan data contoh (minimal 5 baris per tabel), supaya tidak perlu menunggu data dari anggota lain. Seeder aman dijalankan ulang (`php artisan db:seed`), data yang sudah ada tidak digandakan. Untuk mengulang dari nol: `php artisan migrate:fresh --seed`.

Semua akun memakai kata sandi `password`:

| Peran | Email |
|---|---|
| Admin | `admin@titipanak.test` |
| Pengasuh | `pengasuh1@titipanak.test` sampai `pengasuh5@titipanak.test` |
| Orang Tua | `ortu1@titipanak.test` sampai `ortu6@titipanak.test` |

Cerita datanya: pengasuh ke-N memegang kelompok usia ke-N. Orang tua 1-5 punya satu anak aktif (anak 1-5) dengan tagihan, kehadiran, dan laporan. Orang tua 6 punya pendaftaran yang menunggu verifikasi. Pendaftaran berstatus draft dan ditolak juga tersedia untuk uji alur Admin.

Kolom foto (`path_foto_identitas`, `path_bukti`, `path_file`) di data contoh berisi URL gambar karakter Kimetsu no Yaiba dari CDN AniList (lihat `database/seeders/Concerns/FotoContoh.php`), jadi perlu internet agar gambarnya tampil. Di aplikasi sebenarnya kolom ini berisi path file di disk private. Saat menampilkan foto, tangani dua kasus: nilai yang diawali `http` dipakai apa adanya, selain itu diambil dari storage.

## Role dan permission

Peran memakai Spatie Permission: `admin`, `pengasuh`, `orang_tua`. Model `User` sudah memakai trait `HasRoles`. Role dan permission dibuat oleh `RolePermissionSeeder`. Middleware alias Spatie (`role`, `permission`) belum didaftarkan di `bootstrap/app.php`.

## Perintah berguna

```bash
php artisan test            # jalankan test
npm run build               # build aset produksi
php artisan migrate:fresh   # reset database lokal
```
