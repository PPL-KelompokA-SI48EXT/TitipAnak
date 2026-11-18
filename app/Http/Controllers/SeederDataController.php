<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Halaman lokal untuk melihat isi seluruh tabel hasil seeder. Hanya aktif di environment local.
 */
class SeederDataController extends Controller
{
    /** Kelompok tabel: judul => [warna, tabel...] */
    private const GROUPS = [
        'Akun' => ['yellow', ['pengguna', 'profil_orang_tua', 'profil_pengasuh']],
        'Role & Permission' => ['blue', ['roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions']],
        'Master Data' => ['mint', ['kelompok_usia', 'paket_layanan', 'menu_makan', 'pengumuman']],
        'Pendaftaran & Keuangan' => ['lavender', ['pendaftaran', 'anak', 'catatan_kesehatan', 'penjemput_terdaftar', 'tagihan', 'pembayaran']],
        'Jadwal & Operasional' => ['coral', ['jadwal_shift', 'jadwal_kegiatan', 'izin_penjemputan', 'kehadiran']],
        'Laporan Anak' => ['yellow', ['laporan_harian', 'lampiran_laporan', 'laporan_insiden', 'laporan_perkembangan']],
    ];

    /** Kolom yang tidak boleh ditampilkan. */
    private const HIDDEN_COLUMNS = ['password', 'remember_token'];

    public function __invoke(): View
    {
        abort_unless(app()->environment('local'), 404);

        $groups = [];
        foreach (self::GROUPS as $title => [$color, $tables]) {
            $items = [];
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $rows = DB::table($table)->get()->map(
                    fn ($row) => collect((array) $row)->except(self::HIDDEN_COLUMNS)->all()
                );

                $items[] = ['name' => $table, 'rows' => $rows];
            }
            $groups[] = ['title' => $title, 'color' => $color, 'tables' => $items];
        }

        return view('seeder-data', ['groups' => $groups]);
    }
}
