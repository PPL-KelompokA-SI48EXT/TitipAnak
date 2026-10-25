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
php artisan migrate
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

## Role dan permission

Peran memakai Spatie Permission: `admin`, `pengasuh`, `orang_tua`. Model `User` sudah memakai trait `HasRoles`. Seeder role dan akun admin awal belum tersedia.

## Perintah berguna

```bash
php artisan test            # jalankan test
npm run build               # build aset produksi
php artisan migrate:fresh   # reset database lokal
```
