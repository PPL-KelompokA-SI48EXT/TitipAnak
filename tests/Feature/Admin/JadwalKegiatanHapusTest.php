<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalKegiatanIndex;
use App\Models\JadwalKegiatan;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalKegiatanHapusTest extends TestCase
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
        $this->kegiatan = $this->buat('Tidur siang', 12);
    }

    private function buat(string $nama, int $jam): JadwalKegiatan
    {
        return JadwalKegiatan::create([
            'kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => 'tidur', 'hari' => null,
            'jam_mulai' => sprintf('%02d:00:00', $jam), 'jam_selesai' => sprintf('%02d:30:00', $jam),
        ]);
    }

    public function test_tombol_hapus_tampil_di_daftar(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->assertSee('Hapus kegiatan Tidur siang');
    }

    public function test_dialog_konfirmasi_menampilkan_detail_kegiatan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->assertDontSee('Hapus kegiatan?')
            ->call('konfirmasiHapus', $this->kegiatan->id)
            ->assertSet('hapusId', $this->kegiatan->id)
            ->assertSee('Hapus kegiatan?')
            ->assertSee('Batita')
            ->assertSee('Ya, hapus');
    }

    public function test_batal_menutup_dialog_tanpa_menghapus(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->call('konfirmasiHapus', $this->kegiatan->id)
            ->call('batalHapus')
            ->assertSet('hapusId', null)
            ->assertDontSee('Hapus kegiatan?');

        $this->assertDatabaseHas('jadwal_kegiatan', ['id' => $this->kegiatan->id]);
    }

    public function test_id_tidak_dikenal_tidak_membuka_dialog(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->call('konfirmasiHapus', 9999)
            ->assertSet('hapusId', null);
    }
}
