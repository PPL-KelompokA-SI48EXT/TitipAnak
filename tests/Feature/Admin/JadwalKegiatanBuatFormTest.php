<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalKegiatanBuat;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalKegiatanBuatFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
    }

    public function test_admin_membuka_form_dengan_pilihan_kelompok_dan_hari(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-kegiatan.buat'))
            ->assertOk()
            ->assertSee('Buat Jadwal Kegiatan')
            ->assertSee('Batita')
            ->assertSee('Setiap hari')
            ->assertSee('Tidur siang');
    }

    public function test_pengasuh_dan_tamu_tidak_bisa_membuka_form(): void
    {
        $this->get(route('admin.jadwal-kegiatan.buat'))->assertRedirect();

        $pengasuh = User::factory()->create()->assignRole('pengasuh');
        $this->actingAs($pengasuh)->get(route('admin.jadwal-kegiatan.buat'))->assertForbidden();
    }

    public function test_preset_mengisi_nama_jenis_dan_jam(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanBuat::class)
            ->call('pakaiPreset', 'Tidur siang')
            ->assertSet('nama_kegiatan', 'Tidur siang')
            ->assertSet('jenis_kegiatan', 'tidur')
            ->assertSet('jam_mulai', '12:30')
            ->assertSet('jam_selesai', '14:30');
    }

    public function test_preset_tidak_dikenal_diabaikan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanBuat::class)
            ->call('pakaiPreset', 'Tidak ada')
            ->assertSet('nama_kegiatan', '')
            ->assertSet('jenis_kegiatan', '');
    }

    public function test_kolom_wajib_diperiksa_saat_simpan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanBuat::class)
            ->set('jam_mulai', '')
            ->call('simpan')
            ->assertHasErrors(['kelompok_usia_id', 'nama_kegiatan', 'jenis_kegiatan', 'jam_mulai']);
    }
}
