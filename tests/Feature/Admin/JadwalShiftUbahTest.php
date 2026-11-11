<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalShiftUbah;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalShiftUbahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siti;

    private User $dewi;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    private JadwalShift $jadwal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->siti = User::factory()->create(['nama' => 'Siti Rahmawati'])->assignRole('pengasuh');
        $this->dewi = User::factory()->create(['nama' => 'Dewi Lestari'])->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);

        $this->jadwal = $this->shift($this->siti, 1, '07:00', '15:00');
    }

    private function shift(User $pengasuh, int $hari, string $mulai, string $selesai): JadwalShift
    {
        return JadwalShift::create([
            'pengasuh_id' => $pengasuh->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => $hari,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00',
        ]);
    }

    public function test_admin_membuka_form_dengan_data_jadwal_terisi(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-shift.ubah', $this->jadwal))
            ->assertOk()
            ->assertSee('Ubah Jadwal Shift')
            ->assertSee('Siti Rahmawati')
            ->assertSee('Dewi Lestari');

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->assertSet('pengasuh_id', (string) $this->siti->id)
            ->assertSet('kelompok_usia_id', (string) $this->batita->id)
            ->assertSet('hari', '1')
            ->assertSet('jam_mulai', '07:00')
            ->assertSet('jam_selesai', '15:00');
    }

    public function test_non_admin_ditolak(): void
    {
        $this->actingAs($this->siti)
            ->get(route('admin.jadwal-shift.ubah', $this->jadwal))
            ->assertForbidden();
    }

    public function test_jadwal_yang_tidak_ada_menghasilkan_404(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-shift.ubah', 9999))
            ->assertNotFound();
    }

    public function test_admin_mengganti_pengasuh(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->set('pengasuh_id', (string) $this->dewi->id)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.jadwal-shift.index'));

        $this->assertDatabaseCount('jadwal_shift', 1);
        $this->assertDatabaseHas('jadwal_shift', [
            'id' => $this->jadwal->id,
            'pengasuh_id' => $this->dewi->id,
            'kelompok_usia_id' => $this->batita->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);
    }

    public function test_admin_mengubah_kelompok_hari_dan_jam(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->set([
                'kelompok_usia_id' => (string) $this->balita->id,
                'hari' => '3',
                'jam_mulai' => '08:00',
                'jam_selesai' => '16:00',
            ])
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jadwal_shift', [
            'id' => $this->jadwal->id,
            'pengasuh_id' => $this->siti->id,
            'kelompok_usia_id' => $this->balita->id,
            'hari' => 3,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '16:00:00',
        ]);
    }

    public function test_menyimpan_tanpa_perubahan_tidak_dianggap_bentrok_dengan_dirinya_sendiri(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->call('simpan')
            ->assertHasNoErrors();
    }

    public function test_pengganti_yang_jadwalnya_bentrok_ditolak(): void
    {
        $this->shift($this->dewi, 1, '10:00', '13:00');

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->set('pengasuh_id', (string) $this->dewi->id)
            ->call('simpan')
            ->assertHasErrors(['jam_mulai'])
            ->assertNoRedirect();

        $this->assertDatabaseHas('jadwal_shift', ['id' => $this->jadwal->id, 'pengasuh_id' => $this->siti->id]);
    }

    public function test_pengganti_dengan_shift_di_hari_lain_diizinkan(): void
    {
        $this->shift($this->dewi, 2, '07:00', '15:00');

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->set('pengasuh_id', (string) $this->dewi->id)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jadwal_shift', ['id' => $this->jadwal->id, 'pengasuh_id' => $this->dewi->id]);
    }

    public function test_hanya_pengasuh_aktif_yang_boleh_jadi_pengganti(): void
    {
        $nonaktif = User::factory()->nonaktif()->create()->assignRole('pengasuh');

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->set('pengasuh_id', (string) $nonaktif->id)
            ->call('simpan')
            ->assertHasErrors(['pengasuh_id']);

        $this->assertDatabaseHas('jadwal_shift', ['id' => $this->jadwal->id, 'pengasuh_id' => $this->siti->id]);
    }

    public function test_pengasuh_lama_yang_nonaktif_harus_diganti(): void
    {
        $this->siti->update(['aktif' => false]);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->assertSee('sudah tidak aktif')
            ->assertSee('Siti Rahmawati (nonaktif)')
            ->call('simpan')
            ->assertHasErrors(['pengasuh_id'])
            ->set('pengasuh_id', (string) $this->dewi->id)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jadwal_shift', ['id' => $this->jadwal->id, 'pengasuh_id' => $this->dewi->id]);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftUbah::class, ['jadwal' => $this->jadwal])
            ->set(['jam_mulai' => '15:00', 'jam_selesai' => '07:00'])
            ->call('simpan')
            ->assertHasErrors(['jam_selesai']);
    }

    public function test_daftar_jadwal_punya_tautan_ubah(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-shift.index', ['mode' => 'daftar']))
            ->assertOk()
            ->assertSee(route('admin.jadwal-shift.ubah', $this->jadwal), false);

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-shift.index'))
            ->assertSee(route('admin.jadwal-shift.ubah', $this->jadwal), false);
    }
}
