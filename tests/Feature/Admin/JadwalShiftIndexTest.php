<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalShiftIndex;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalShiftIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siti;

    private User $dewi;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

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
    }

    private function shift(User $pengasuh, KelompokUsia $kelompok, int $hari, string $mulai = '07:00', string $selesai = '15:00'): JadwalShift
    {
        return JadwalShift::create([
            'pengasuh_id' => $pengasuh->id, 'kelompok_usia_id' => $kelompok->id, 'hari' => $hari,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00',
        ]);
    }

    public function test_admin_melihat_seluruh_jadwal(): void
    {
        $this->shift($this->siti, $this->batita, 1);
        $this->shift($this->dewi, $this->balita, 2, '08:00', '16:00');

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-shift.index'))
            ->assertOk()
            ->assertSee('Siti Rahmawati')
            ->assertSee('Dewi Lestari')
            ->assertSee('Batita')
            ->assertSee('Balita')
            ->assertSee('Senin')
            ->assertSee('Selasa')
            ->assertSee('08:00 – 16:00')
            ->assertSee('Menampilkan 2 dari 2 jadwal');
    }

    public function test_non_admin_ditolak(): void
    {
        $this->actingAs($this->siti)
            ->get(route('admin.jadwal-shift.index'))
            ->assertForbidden();
    }

    public function test_jadwal_terurut_berdasarkan_hari_lalu_jam(): void
    {
        $this->shift($this->siti, $this->batita, 3);
        $this->shift($this->dewi, $this->balita, 1, '09:00', '12:00');
        $this->shift($this->siti, $this->batita, 1, '07:00', '09:00');

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-shift.index', ['mode' => 'daftar']))
            ->assertSeeInOrder(['Senin', '07:00 – 09:00', 'Senin', '09:00 – 12:00', 'Rabu']);
    }

    public function test_cari_berdasarkan_nama_pengasuh(): void
    {
        $this->shift($this->siti, $this->batita, 1);
        $this->shift($this->dewi, $this->balita, 2);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->set('q', 'siti')
            ->assertSee('Siti Rahmawati')
            ->assertDontSee('Dewi Lestari')
            ->assertSee('Menampilkan 1 dari 2 jadwal');
    }

    public function test_filter_kelompok_usia_dan_hari(): void
    {
        $this->shift($this->siti, $this->batita, 1);
        $this->shift($this->dewi, $this->balita, 2);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->set('kelompok', (string) $this->balita->id)
            ->assertSee('Dewi Lestari')
            ->assertDontSee('Siti Rahmawati')
            ->set('kelompok', '')
            ->call('setMode', 'daftar')
            ->set('hari', '1')
            ->assertSee('Siti Rahmawati')
            ->assertDontSee('Dewi Lestari');
    }

    public function test_hasil_kosong_dan_reset_filter(): void
    {
        $this->shift($this->siti, $this->batita, 1);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->set('q', 'tidak ada orang ini')
            ->assertSee('Tidak ada hasil')
            ->call('resetFilter')
            ->assertSet('q', '')
            ->assertSee('Siti Rahmawati');
    }

    public function test_kalender_minggu_menampilkan_tanggal_dan_shift_per_hari(): void
    {
        Carbon::setTestNow('2026-10-28 09:00:00'); // Rabu
        $this->shift($this->siti, $this->batita, 1);
        $this->shift($this->dewi, $this->balita, 3, '08:00', '16:00');

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->assertSee('26 Okt – 1 Nov 2026')
            ->assertSee('Siti Rahmawati')
            ->assertSee('Dewi Lestari')
            ->assertSee('08:00 – 16:00')
            ->assertSee('Tidak ada shift'); // hari lain yang kosong
    }

    public function test_navigasi_minggu_dan_kembali_ke_hari_ini(): void
    {
        Carbon::setTestNow('2026-10-28 09:00:00');
        $this->shift($this->siti, $this->batita, 1);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->call('pindah', 1)
            ->assertSee('2 – 8 Nov 2026')
            ->call('pindah', -1)
            ->call('pindah', -1)
            ->assertSee('19 – 25 Okt 2026')
            ->call('hariIni')
            ->assertSee('26 Okt – 1 Nov 2026');
    }

    public function test_kalender_bulan_dan_navigasinya(): void
    {
        Carbon::setTestNow('2026-10-28 09:00:00');
        $this->shift($this->siti, $this->batita, 1);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->call('setMode', 'bulan')
            ->assertSee('Oktober 2026')
            ->assertSee('07:00 Siti')
            ->call('pindah', 1)
            ->assertSee('November 2026')
            ->call('pindah', -1)
            ->call('pindah', -1)
            ->assertSee('September 2026');
    }

    public function test_klik_tanggal_di_bulan_membuka_minggu_tersebut(): void
    {
        Carbon::setTestNow('2026-10-28 09:00:00');
        $this->shift($this->siti, $this->batita, 1);

        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->call('setMode', 'bulan')
            ->call('pilihTanggal', '2026-11-12')
            ->assertSet('mode', 'minggu')
            ->assertSet('tanggal', '2026-11-12')
            ->assertSee('9 – 15 Nov 2026');
    }

    public function test_mode_tidak_valid_jatuh_ke_minggu(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalShiftIndex::class)
            ->call('setMode', 'ngawur')
            ->assertSet('mode', 'minggu');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_keadaan_belum_ada_jadwal(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-shift.index'))
            ->assertOk()
            ->assertSee('Belum ada jadwal shift');
    }
}
