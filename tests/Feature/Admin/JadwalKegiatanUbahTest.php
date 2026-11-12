<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalKegiatanUbah;
use App\Models\JadwalKegiatan;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalKegiatanUbahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    private JadwalKegiatan $kegiatan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
        $this->kegiatan = $this->buat($this->batita, 'Makan siang', 'makan', 2, '11:30', '12:15');
    }

    private function buat(KelompokUsia $k, string $nama, string $jenis, ?int $hari, string $mulai, string $selesai): JadwalKegiatan
    {
        return JadwalKegiatan::create([
            'kelompok_usia_id' => $k->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => $jenis, 'hari' => $hari,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00',
        ]);
    }

    private function form(?JadwalKegiatan $kegiatan = null)
    {
        return Livewire::actingAs($this->admin)->test(JadwalKegiatanUbah::class, ['kegiatan' => $kegiatan ?? $this->kegiatan]);
    }

    public function test_mengubah_semua_kolom_kegiatan(): void
    {
        $this->form()
            ->set('kelompok_usia_id', (string) $this->balita->id)
            ->set('nama_kegiatan', '  Makan siang bersama ')
            ->set('jenis_kegiatan', 'lainnya')
            ->set('hari', '')
            ->set('jam_mulai', '12:00')
            ->set('jam_selesai', '12:45')
            ->set('catatan', 'Bawa bekal')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.jadwal-kegiatan.index'));

        $this->assertDatabaseHas('jadwal_kegiatan', [
            'id' => $this->kegiatan->id, 'kelompok_usia_id' => $this->balita->id, 'nama_kegiatan' => 'Makan siang bersama',
            'jenis_kegiatan' => 'lainnya', 'hari' => null, 'jam_mulai' => '12:00:00', 'jam_selesai' => '12:45:00', 'catatan' => 'Bawa bekal',
        ]);
        $this->assertDatabaseCount('jadwal_kegiatan', 1);
    }

    public function test_menyimpan_tanpa_mengubah_jam_tidak_dianggap_bentrok_dengan_dirinya(): void
    {
        $this->form()
            ->set('catatan', 'Hanya catatan baru')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jadwal_kegiatan', ['id' => $this->kegiatan->id, 'catatan' => 'Hanya catatan baru']);
    }

    public function test_perubahan_yang_bertabrakan_dengan_kegiatan_lain_ditolak(): void
    {
        $this->buat($this->batita, 'Bermain', 'bermain', null, '12:00', '13:00');

        $this->form()
            ->set('jam_selesai', '12:30')
            ->call('simpan')
            ->assertHasErrors(['jam_mulai']);

        $this->assertDatabaseHas('jadwal_kegiatan', ['id' => $this->kegiatan->id, 'jam_selesai' => '12:15:00']);
    }

    public function test_validasi_sama_dengan_pembuatan(): void
    {
        $this->form()
            ->set('jam_mulai', '13:00')
            ->set('jam_selesai', '12:00')
            ->set('jenis_kegiatan', 'asal')
            ->set('nama_kegiatan', '')
            ->call('simpan')
            ->assertHasErrors(['jam_selesai', 'jenis_kegiatan', 'nama_kegiatan']);

        $this->assertDatabaseHas('jadwal_kegiatan', ['id' => $this->kegiatan->id, 'nama_kegiatan' => 'Makan siang']);
    }

    public function test_id_kegiatan_tidak_bisa_dimanipulasi_dari_klien(): void
    {
        $lain = $this->buat($this->balita, 'Senam', 'bermain', 1, '09:00', '09:30');

        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->form()->set('kegiatanId', $lain->id);
    }

    public function test_pengguna_tanpa_izin_tidak_boleh_menyimpan(): void
    {
        $pengasuh = User::factory()->create()->assignRole('pengasuh');

        Livewire::actingAs($pengasuh)
            ->test(JadwalKegiatanUbah::class, ['kegiatan' => $this->kegiatan])
            ->call('simpan')
            ->assertForbidden();
    }
}
