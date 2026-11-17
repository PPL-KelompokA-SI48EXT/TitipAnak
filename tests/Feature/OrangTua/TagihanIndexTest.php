<?php

namespace Tests\Feature\OrangTua;

use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TagihanIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $orangTua;

    private User $orangTuaLain;

    private User $pengasuh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
        $this->orangTuaLain = User::factory()->create()->assignRole('orang_tua');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
    }

    private function buatTagihan(User $orangTua, string $status = 'belum_lunas', int $harga = 1500000, ?string $nomor = null): int
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket '.fake()->unique()->numberBetween(1000, 9999),
            'harga' => $harga,
            'aktif' => true,
        ]);

        $pendaftaranId = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $orangTua->id,
            'paket_layanan_id' => $paket->id,
            'status' => 'disetujui',
            'diajukan_pada' => now(),
            'diverifikasi_oleh_id' => null,
            'diverifikasi_pada' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('tagihan')->insertGetId([
            'pendaftaran_id' => $pendaftaranId,
            'nomor_tagihan' => $nomor ?? 'TGH-'.now()->format('Ym').'-'.fake()->unique()->numberBetween(1000, 9999),
            'jumlah' => $harga,
            'status' => $status,
            'diterbitkan_pada' => now()->subDay(),
            'jatuh_tempo' => today()->addDays(9),
            'lunas_pada' => $status === 'lunas' ? now() : null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
    }

    public function test_orang_tua_dapat_melihat_rincian_dan_status_tagihan_miliknya(): void
    {
        $this->buatTagihan($this->orangTua, 'belum_lunas', 1500000, 'TGH-OWN-0001');
        $this->buatTagihan($this->orangTua, 'lunas', 2750000, 'TGH-OWN-0002');

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.tagihan.index'))
            ->assertOk()
            ->assertSee('Rincian Tagihan')
            ->assertSee('TGH-OWN-0001')
            ->assertSee('TGH-OWN-0002')
            ->assertSee('Rp 1.500.000,00')
            ->assertSee('Rp 2.750.000,00')
            ->assertSee('Belum Lunas')
            ->assertSee('Lunas')
            ->assertSee('Jatuh tempo');
    }

    public function test_orang_tua_hanya_melihat_tagihan_miliknya(): void
    {
        $this->buatTagihan($this->orangTua, 'belum_lunas', 1500000, 'TGH-MILIK-0001');
        $this->buatTagihan($this->orangTuaLain, 'belum_lunas', 2750000, 'TGH-ORANG-0002');

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.tagihan.index'))
            ->assertOk()
            ->assertSee('TGH-MILIK-0001')
            ->assertDontSee('TGH-ORANG-0002');
    }

    public function test_tagihan_soft_deleted_tidak_tampil_di_daftar_orang_tua(): void
    {
        $tagihanId = $this->buatTagihan($this->orangTua, 'belum_lunas', 1500000, 'TGH-AKTIF-0001');
        DB::table('tagihan')->where('id', $tagihanId)->update(['deleted_at' => now()]);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.tagihan.index'))
            ->assertOk()
            ->assertSee('Belum ada tagihan')
            ->assertDontSee('TGH-AKTIF-0001');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('orang-tua.tagihan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $this->actingAs($this->pengasuh)
            ->get(route('orang-tua.tagihan.index'))
            ->assertForbidden();
    }
}
