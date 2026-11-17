<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalShiftIndex;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalShiftHapusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siti;

    private KelompokUsia $batita;

    private JadwalShift $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->siti = User::factory()->create(['nama' => 'Siti Rahmawati'])->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->jadwal = JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => 1,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
    }

    public function test_tombol_hapus_tampil_di_daftar(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class, ['mode' => 'daftar'])
            ->assertSee('Hapus');
    }

    public function test_dialog_konfirmasi_menampilkan_detail_jadwal(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->assertDontSee('Hapus jadwal shift?')
            ->call('konfirmasiHapus', $this->jadwal->id)
            ->assertSet('hapusId', $this->jadwal->id)
            ->assertSee('Hapus jadwal shift?')
            ->assertSee('Siti Rahmawati')
            ->assertSee('Ya, hapus');
    }

    public function test_batal_menutup_dialog_tanpa_menghapus(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->call('konfirmasiHapus', $this->jadwal->id)
            ->call('batalHapus')
            ->assertSet('hapusId', null)
            ->assertDontSee('Hapus jadwal shift?');

        $this->assertDatabaseHas('jadwal_shift', ['id' => $this->jadwal->id]);
    }

    public function test_id_tidak_dikenal_tidak_membuka_dialog(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->call('konfirmasiHapus', 9999)
            ->assertSet('hapusId', null);
    }
}
