<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\PaketLayananCreate;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaketLayananCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pengasuh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
    }

    public function test_admin_dapat_membuka_halaman_tambah_paket(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.paket-layanan.create'))
            ->assertOk()
            ->assertSee('Tambah paket')
            ->assertSee('Kembali ke daftar');
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('admin.paket-layanan.create'))
            ->assertStatus(302);
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $this->actingAs($this->pengasuh)
            ->get(route('admin.paket-layanan.create'))
            ->assertForbidden();
    }

    public function test_admin_dapat_menambahkan_paket_dengan_status_aktif_dan_diarahkan_ke_daftar(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PaketLayananCreate::class)
            ->set('nama', 'Paket Premium')
            ->set('deskripsi', 'Layanan premium dengan laporan harian.')
            ->set('harga', '3250000')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.paket-layanan.index'));

        $this->assertDatabaseHas('paket_layanan', [
            'nama' => 'Paket Premium',
            'deskripsi' => 'Layanan premium dengan laporan harian.',
            'harga' => '3250000.00',
            'aktif' => true,
        ]);
    }

    public function test_field_wajib_diisi_dan_tarif_minimal_satu_rupiah(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PaketLayananCreate::class)
            ->set(['nama' => '', 'harga' => '0'])
            ->call('simpan')
            ->assertHasErrors(['nama', 'harga']);

        $this->assertDatabaseCount('paket_layanan', 0);
    }

    public function test_nama_paket_tidak_boleh_duplikat(): void
    {
        PaketLayanan::create([
            'nama' => 'Full Day',
            'deskripsi' => 'Paket existing.',
            'harga' => 2500000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananCreate::class)
            ->set(['nama' => 'Full Day', 'harga' => '3000000'])
            ->call('simpan')
            ->assertHasErrors(['nama']);
    }

    public function test_deskripsi_boleh_kosong(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PaketLayananCreate::class)
            ->set(['nama' => 'Paket Tanpa Deskripsi', 'harga' => '100000'])
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('paket_layanan', [
            'nama' => 'Paket Tanpa Deskripsi',
            'deskripsi' => null,
        ]);
    }
}
