<?php

namespace Tests\Feature\OrangTua;

use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaketLayananIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $orangTua;

    private User $pengasuh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
    }

    public function test_orang_tua_dapat_melihat_daftar_paket_aktif_dan_harganya(): void
    {
        PaketLayanan::create([
            'nama' => 'Paket Harian Aktif',
            'deskripsi' => 'Layanan harian.',
            'harga' => 100000,
            'aktif' => true,
        ]);

        PaketLayanan::create([
            'nama' => 'Paket Bulanan Nonaktif',
            'deskripsi' => 'Layanan bulanan.',
            'harga' => 500000,
            'aktif' => false,
        ]);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.paket-layanan.index'))
            ->assertOk()
            ->assertSee('Paket Harian Aktif')
            ->assertSee('Rp 100.000,00')
            ->assertDontSee('Paket Bulanan Nonaktif');
    }

    public function test_orang_tua_tidak_melihat_kolom_aksi_atau_status(): void
    {
        PaketLayanan::create([
            'nama' => 'Paket Tampil',
            'harga' => 150000,
            'aktif' => true,
        ]);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.paket-layanan.index'))
            ->assertOk()
            ->assertDontSee('Edit')
            ->assertDontSee('Nonaktifkan')
            ->assertDontSee('Hapus')
            ->assertDontSee('Aktif');
    }

    public function test_tamu_ditolak_oleh_middleware_auth(): void
    {
        $this->get(route('orang-tua.paket-layanan.index'))
            ->assertStatus(302);
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $this->actingAs($this->pengasuh)
            ->get(route('orang-tua.paket-layanan.index'))
            ->assertForbidden();
    }
}
