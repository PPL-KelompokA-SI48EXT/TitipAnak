<?php

namespace Tests\Feature\Pengasuh;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalKegiatanHarianTest extends TestCase
{
    use RefreshDatabase;

    private User $siti;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        // Jam dibekukan agar penanda "sedang berlangsung" tidak bergantung pada kapan tes dijalankan.
        Carbon::setTestNow(today()->setTime(10, 30));

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function shiftHariIni(KelompokUsia $k): void
    {
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $k->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '00:00:00', 'jam_selesai' => '23:59:00',
        ]);
    }

    private function kegiatan(KelompokUsia $k, string $nama, string $mulai, string $selesai, ?string $catatan = null): void
    {
        JadwalKegiatan::create([
            'kelompok_usia_id' => $k->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => 'makan', 'hari' => null,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00', 'catatan' => $catatan,
        ]);
    }

    public function test_pengasuh_melihat_kegiatan_kelompok_yang_dipegang_hari_ini(): void
    {
        $this->shiftHariIni($this->batita);
        $this->kegiatan($this->batita, 'Sarapan Batita', '07:30', '08:00', 'Tanpa kacang');
        $this->kegiatan($this->balita, 'Kegiatan Balita', '08:00', '09:00');

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-kegiatan'))
            ->assertOk()
            ->assertSee('Sarapan Batita')
            ->assertSee('07:30 – 08:00')
            ->assertSee('Tanpa kacang')
            ->assertDontSee('Kegiatan Balita');
    }

    public function test_kegiatan_yang_sedang_berlangsung_ditandai(): void
    {
        $this->shiftHariIni($this->batita);
        $this->kegiatan($this->batita, 'Sepanjang hari', '00:00', '23:59');

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-kegiatan'))
            ->assertSee('Sedang berlangsung');
    }

    public function test_kegiatan_yang_belum_mulai_tidak_ditandai(): void
    {
        $this->shiftHariIni($this->batita);
        $this->kegiatan($this->batita, 'Nanti', '23:58', '23:59');

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-kegiatan'))
            ->assertSee('Nanti')
            ->assertDontSee('Sedang berlangsung');
    }

    public function test_tanpa_shift_hari_ini_menampilkan_keadaan_kosong(): void
    {
        $this->kegiatan($this->batita, 'Sarapan', '07:30', '08:00');

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-kegiatan'))
            ->assertOk()
            ->assertSee('Tidak ada kegiatan hari ini')
            ->assertDontSee('Sarapan');
    }

    public function test_admin_dan_tamu_tidak_bisa_membuka_halaman(): void
    {
        $this->get(route('pengasuh.jadwal-kegiatan'))->assertRedirect();

        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->get(route('pengasuh.jadwal-kegiatan'))->assertForbidden();
    }

    public function test_menu_jadwal_kegiatan_tampil_untuk_pengasuh(): void
    {
        $this->actingAs($this->siti)
            ->get(route('dashboard'))
            ->assertSee(route('pengasuh.jadwal-kegiatan'), false);
    }
}
