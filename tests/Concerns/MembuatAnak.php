<?php

namespace Tests\Concerns;

use App\Models\Anak;
use App\Models\KelompokUsia;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Helper tes: membuat anak lengkap dengan orang tua, paket, dan pendaftarannya. */
trait MembuatAnak
{
    protected function buatAnak(string $nama, ?KelompokUsia $kelompok, string $status = 'aktif', ?User $orangTua = null): Anak
    {
        $orangTua ??= User::factory()->create();

        $paketId = DB::table('paket_layanan')->insertGetId([
            'nama' => 'Paket '.$nama, 'harga' => 1000000, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $pendaftaranId = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $orangTua->id, 'paket_layanan_id' => $paketId, 'status' => 'disetujui',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Anak::create([
            'pendaftaran_id' => $pendaftaranId, 'orang_tua_id' => $orangTua->id, 'kelompok_usia_id' => $kelompok?->id,
            'nama_lengkap' => $nama, 'tanggal_lahir' => '2024-01-01', 'jenis_kelamin' => 'L', 'status' => $status,
        ]);
    }
}
