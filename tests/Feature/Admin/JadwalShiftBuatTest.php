<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalShiftBuat;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalShiftBuatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pengasuh;

    private KelompokUsia $kelompok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
        $this->kelompok = KelompokUsia::create([
            'nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12,
        ]);
    }

    private function isiForm(array $override = []): array
    {
        return $override + [
            'pengasuh_id' => (string) $this->pengasuh->id,
            'kelompok_usia_id' => (string) $this->kelompok->id,
            'hari' => '1',
            'jam_mulai' => '07:00',
            'jam_selesai' => '15:00',
        ];
    }

    public function test_admin_dapat_membuka_halaman(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-shift.buat'))
            ->assertOk()
            ->assertSee('Buat Jadwal Shift')
            ->assertSee($this->pengasuh->nama);
    }

    public function test_non_admin_ditolak(): void
    {
        $this->actingAs($this->pengasuh)
            ->get(route('admin.jadwal-shift.buat'))
            ->assertForbidden();
    }

    public function test_admin_membuat_jadwal_shift(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftBuat::class)
            ->set($this->isiForm())
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jadwal_shift', [
            'pengasuh_id' => $this->pengasuh->id,
            'kelompok_usia_id' => $this->kelompok->id,
            'hari' => 1,
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '15:00:00',
        ]);
    }

    public function test_semua_field_wajib_diisi(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftBuat::class)
            ->set(['jam_mulai' => '', 'jam_selesai' => ''])
            ->call('simpan')
            ->assertHasErrors(['pengasuh_id', 'kelompok_usia_id', 'hari', 'jam_mulai', 'jam_selesai']);

        $this->assertDatabaseCount('jadwal_shift', 0);
    }

    public function test_jam_selesai_harus_setelah_jam_mulai(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftBuat::class)
            ->set($this->isiForm(['jam_mulai' => '15:00', 'jam_selesai' => '07:00']))
            ->call('simpan')
            ->assertHasErrors(['jam_selesai']);
    }

    public function test_hanya_pengasuh_aktif_yang_boleh_dipilih(): void
    {
        $orangTua = User::factory()->create()->assignRole('orang_tua');
        $nonaktif = User::factory()->nonaktif()->create()->assignRole('pengasuh');

        foreach ([$orangTua, $nonaktif] as $bukanPengasuhAktif) {
            Livewire::actingAs($this->admin)
                ->test(JadwalShiftBuat::class)
                ->set($this->isiForm(['pengasuh_id' => (string) $bukanPengasuhAktif->id]))
                ->call('simpan')
                ->assertHasErrors(['pengasuh_id']);
        }

        $this->assertDatabaseCount('jadwal_shift', 0);
    }

    public function test_shift_bertabrakan_untuk_pengasuh_yang_sama_ditolak(): void
    {
        JadwalShift::create([
            'pengasuh_id' => $this->pengasuh->id, 'kelompok_usia_id' => $this->kelompok->id,
            'hari' => 1, 'jam_mulai' => '07:00:00', 'jam_selesai' => '12:00:00',
        ]);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftBuat::class)
            ->set($this->isiForm(['jam_mulai' => '11:00', 'jam_selesai' => '15:00']))
            ->call('simpan')
            ->assertHasErrors(['jam_mulai']);

        $this->assertDatabaseCount('jadwal_shift', 1);
    }

    public function test_shift_berurutan_tanpa_tabrakan_diizinkan(): void
    {
        JadwalShift::create([
            'pengasuh_id' => $this->pengasuh->id, 'kelompok_usia_id' => $this->kelompok->id,
            'hari' => 1, 'jam_mulai' => '07:00:00', 'jam_selesai' => '12:00:00',
        ]);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftBuat::class)
            ->set($this->isiForm(['jam_mulai' => '12:00', 'jam_selesai' => '15:00']))
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('jadwal_shift', 2);
    }
}
