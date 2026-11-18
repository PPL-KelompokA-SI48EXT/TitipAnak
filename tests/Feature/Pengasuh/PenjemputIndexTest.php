<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\PenjemputIndex;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PenjemputIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $pengasuh;

    private User $orangTua;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
    }

    private function buatAnak(User $orangTua, string $nama, string $status = 'aktif'): int
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket '.fake()->unique()->numberBetween(1000, 9999),
            'harga' => 100000,
            'aktif' => true,
        ]);

        $pendaftaranId = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $orangTua->id,
            'paket_layanan_id' => $paket->id,
            'status' => 'disetujui',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('anak')->insertGetId([
            'pendaftaran_id' => $pendaftaranId,
            'orang_tua_id' => $orangTua->id,
            'nama_lengkap' => $nama,
            'tanggal_lahir' => '2022-01-01',
            'jenis_kelamin' => 'P',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buatPenjemput(int $anakId, string $nama, bool $aktif = true): void
    {
        DB::table('penjemput_terdaftar')->insert([
            'anak_id' => $anakId,
            'nama_lengkap' => $nama,
            'hubungan' => 'Kakek',
            'no_hp' => '081234567890',
            'path_foto_identitas' => 'penjemput-identitas/'.strtolower(str_replace(' ', '-', $nama)).'.png',
            'aktif' => $aktif,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_pengasuh_dapat_melihat_daftar_penjemput_aktif(): void
    {
        $anakAktif = $this->buatAnak($this->orangTua, 'Nadia Aktif');
        $anakNonaktif = $this->buatAnak($this->orangTua, 'Nadia Nonaktif', 'nonaktif');
        $this->buatPenjemput($anakAktif, 'Budi Aktif');
        $this->buatPenjemput($anakAktif, 'Budi Tidak Berwenang', false);
        $this->buatPenjemput($anakNonaktif, 'Budi Anak Nonaktif');

        $this->actingAs($this->pengasuh)
            ->get(route('pengasuh.penjemput.index'))
            ->assertOk()
            ->assertSee('Daftar Penjemput Terdaftar')
            ->assertSee('Nadia Aktif')
            ->assertSee('Budi Aktif')
            ->assertDontSee('Budi Tidak Berwenang')
            ->assertDontSee('Budi Anak Nonaktif')
            ->assertDontSee('Ubah')
            ->assertDontSee('Hapus');
    }

    public function test_daftar_menampilkan_penjemput_dari_semua_orang_tua(): void
    {
        $orangTuaLain = User::factory()->create()->assignRole('orang_tua');
        $anak = $this->buatAnak($orangTuaLain, 'Anak Orang Tua Lain');
        $this->buatPenjemput($anak, 'Penjemput Orang Tua Lain');

        $this->actingAs($this->pengasuh)
            ->get(route('pengasuh.penjemput.index'))
            ->assertOk()
            ->assertSee('Anak Orang Tua Lain')
            ->assertSee('Penjemput Orang Tua Lain');
    }

    public function test_tamu_ditolak_oleh_middleware_auth(): void
    {
        $this->get(route('pengasuh.penjemput.index'))
            ->assertRedirect(route('login'));
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $this->actingAs($this->orangTua)
            ->get(route('pengasuh.penjemput.index'))
            ->assertForbidden();
    }

    public function test_pengasuh_dapat_melihat_dan_menutup_foto_identitas_penjemput(): void
    {
        Storage::fake('local');
        $path = 'penjemput-identitas/budi-foto.png';
        Storage::disk('local')->put($path, 'dummy image content');

        $anak = $this->buatAnak($this->orangTua, 'Anak Asuh');
        $penjemputId = DB::table('penjemput_terdaftar')->insertGetId([
            'anak_id' => $anak,
            'nama_lengkap' => 'Budi Penjemput',
            'hubungan' => 'Kakek',
            'no_hp' => '081234567890',
            'path_foto_identitas' => $path,
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($this->pengasuh)
            ->test(PenjemputIndex::class)
            ->assertSee('Lihat foto')
            ->call('lihatFoto', $penjemputId)
            ->assertSet('viewingFotoId', $penjemputId)
            ->assertSee('Foto Identitas Penjemput')
            ->assertSee('Budi Penjemput')
            ->call('tutupLihatFoto')
            ->assertSet('viewingFotoId', null);
    }
}
