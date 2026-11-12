<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalKegiatanUbah;
use App\Models\JadwalKegiatan;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalKegiatanUbahFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private KelompokUsia $batita;

    private JadwalKegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->kegiatan = JadwalKegiatan::create([
            'kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => 'Makan siang', 'jenis_kegiatan' => 'makan',
            'hari' => 2, 'jam_mulai' => '11:30:00', 'jam_selesai' => '12:15:00', 'catatan' => 'Tanpa kacang',
        ]);
    }

    public function test_form_ubah_terisi_data_kegiatan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-kegiatan.ubah', $this->kegiatan))
            ->assertOk()
            ->assertSee('Ubah Jadwal Kegiatan')
            ->assertSee('Simpan perubahan')
            ->assertDontSee('Kegiatan rutin');

        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanUbah::class, ['kegiatan' => $this->kegiatan])
            ->assertSet('kelompok_usia_id', (string) $this->batita->id)
            ->assertSet('nama_kegiatan', 'Makan siang')
            ->assertSet('jenis_kegiatan', 'makan')
            ->assertSet('hari', '2')
            ->assertSet('jam_mulai', '11:30')
            ->assertSet('jam_selesai', '12:15')
            ->assertSet('catatan', 'Tanpa kacang');
    }

    public function test_kegiatan_setiap_hari_ditampilkan_dengan_hari_kosong(): void
    {
        $harian = JadwalKegiatan::create([
            'kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => 'Sarapan', 'jenis_kegiatan' => 'makan',
            'hari' => null, 'jam_mulai' => '07:30:00', 'jam_selesai' => '08:00:00',
        ]);

        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanUbah::class, ['kegiatan' => $harian])
            ->assertSet('hari', '')
            ->assertSet('catatan', '');
    }

    public function test_id_tidak_ada_menghasilkan_404(): void
    {
        $this->actingAs($this->admin)->get(route('admin.jadwal-kegiatan.ubah', 9999))->assertNotFound();
    }

    public function test_pengasuh_tidak_bisa_membuka_form_ubah(): void
    {
        $pengasuh = User::factory()->create()->assignRole('pengasuh');

        $this->actingAs($pengasuh)->get(route('admin.jadwal-kegiatan.ubah', $this->kegiatan))->assertForbidden();
    }

    public function test_daftar_kegiatan_punya_tautan_ubah(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-kegiatan.index'))
            ->assertSee(route('admin.jadwal-kegiatan.ubah', $this->kegiatan), false);
    }
}
