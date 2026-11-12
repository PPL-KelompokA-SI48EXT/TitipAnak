<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalKegiatanBuat;
use App\Models\JadwalKegiatan;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalKegiatanBuatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
    }

    /** @param  array<string, string>  $nilai */
    private function isiForm(array $nilai = []): Testable
    {
        $nilai += [
            'kelompok_usia_id' => (string) $this->batita->id, 'nama_kegiatan' => 'Sarapan', 'jenis_kegiatan' => 'makan',
            'hari' => '', 'jam_mulai' => '07:30', 'jam_selesai' => '08:00', 'catatan' => '',
        ];

        $komponen = Livewire::actingAs($this->admin)->test(JadwalKegiatanBuat::class);
        foreach ($nilai as $kolom => $isi) {
            $komponen->set($kolom, $isi);
        }

        return $komponen;
    }

    private function kegiatan(KelompokUsia $k, string $mulai, string $selesai): JadwalKegiatan
    {
        return JadwalKegiatan::create([
            'kelompok_usia_id' => $k->id, 'nama_kegiatan' => 'Bermain', 'jenis_kegiatan' => 'bermain',
            'hari' => null, 'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00',
        ]);
    }

    public function test_menyimpan_kegiatan_setiap_hari(): void
    {
        $this->isiForm(['catatan' => '  Tanpa kacang  '])
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('Jadwal kegiatan berhasil dibuat.')
            ->assertSet('nama_kegiatan', '');

        $this->assertDatabaseHas('jadwal_kegiatan', [
            'kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => 'Sarapan', 'jenis_kegiatan' => 'makan',
            'hari' => null, 'jam_mulai' => '07:30:00', 'jam_selesai' => '08:00:00', 'catatan' => 'Tanpa kacang',
        ]);
    }

    public function test_menyimpan_kegiatan_untuk_hari_tertentu(): void
    {
        $this->isiForm(['hari' => '3', 'nama_kegiatan' => 'Senam', 'jenis_kegiatan' => 'bermain'])
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jadwal_kegiatan', ['nama_kegiatan' => 'Senam', 'hari' => 3]);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        $this->isiForm(['jam_mulai' => '09:00', 'jam_selesai' => '09:00'])
            ->call('simpan')
            ->assertHasErrors(['jam_selesai']);

        $this->assertDatabaseCount('jadwal_kegiatan', 0);
    }

    public function test_jenis_hari_dan_kelompok_harus_valid(): void
    {
        $this->isiForm(['jenis_kegiatan' => 'asal', 'hari' => '9', 'kelompok_usia_id' => '999'])
            ->call('simpan')
            ->assertHasErrors(['jenis_kegiatan', 'hari', 'kelompok_usia_id']);
    }

    public function test_format_jam_dan_panjang_teks_dibatasi(): void
    {
        $this->isiForm(['jam_mulai' => 'pagi', 'nama_kegiatan' => str_repeat('a', 101), 'catatan' => str_repeat('b', 501)])
            ->call('simpan')
            ->assertHasErrors(['jam_mulai', 'nama_kegiatan', 'catatan']);
    }

    public function test_kegiatan_bertabrakan_di_kelompok_yang_sama_ditolak(): void
    {
        $this->kegiatan($this->batita, '08:00', '09:30');

        $this->isiForm(['hari' => '2', 'jam_mulai' => '09:00', 'jam_selesai' => '10:00'])
            ->call('simpan')
            ->assertHasErrors(['jam_mulai']);

        $this->assertDatabaseCount('jadwal_kegiatan', 1);
    }

    public function test_jam_menempel_atau_kelompok_lain_tidak_dianggap_bentrok(): void
    {
        $this->kegiatan($this->batita, '08:00', '09:30');

        $this->isiForm(['jam_mulai' => '09:30', 'jam_selesai' => '10:00', 'nama_kegiatan' => 'Camilan'])
            ->call('simpan')->assertHasNoErrors();
        $this->isiForm(['kelompok_usia_id' => (string) $this->balita->id, 'jam_mulai' => '08:30', 'jam_selesai' => '09:00'])
            ->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseCount('jadwal_kegiatan', 3);
    }

    public function test_pengguna_tanpa_izin_tidak_boleh_menyimpan(): void
    {
        $pengasuh = User::factory()->create()->assignRole('pengasuh');

        Livewire::actingAs($pengasuh)
            ->test(JadwalKegiatanBuat::class)
            ->call('simpan')
            ->assertForbidden();
    }
}
