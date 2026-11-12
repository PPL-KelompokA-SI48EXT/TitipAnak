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

    public function test_konfirmasi_menghapus_kegiatan_dan_menutup_dialog(): void
    {
        $lain = $this->buat('Makan siang', 11);

        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->call('konfirmasiHapus', $this->kegiatan->id)
            ->call('hapus')
            ->assertSet('hapusId', null)
            ->assertSee('Kegiatan berhasil dihapus.')
            ->assertDontSee('Hapus kegiatan Tidur siang');

        $this->assertDatabaseMissing('jadwal_kegiatan', ['id' => $this->kegiatan->id]);
        $this->assertDatabaseHas('jadwal_kegiatan', ['id' => $lain->id]);
    }

    public function test_hapus_tanpa_konfirmasi_tidak_menghapus_apa_pun(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->call('hapus');

        $this->assertDatabaseCount('jadwal_kegiatan', 1);
    }

    public function test_kegiatan_yang_sudah_terhapus_tidak_menimbulkan_error(): void
    {
        $komponen = Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->call('konfirmasiHapus', $this->kegiatan->id);

        $this->kegiatan->delete();

        $komponen->call('hapus')->assertSet('hapusId', null)->assertSee('sudah tidak ada');
    }

    public function test_pengasuh_tidak_boleh_menghapus_kegiatan(): void
    {
        $pengasuh = User::factory()->create()->assignRole('pengasuh');

        Livewire::actingAs($pengasuh)
            ->test(JadwalKegiatanIndex::class)
            ->call('konfirmasiHapus', $this->kegiatan->id)
            ->call('hapus')
            ->assertForbidden();

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
