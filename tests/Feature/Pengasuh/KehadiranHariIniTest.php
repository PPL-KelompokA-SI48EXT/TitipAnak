<?php

namespace Tests\Feature\Pengasuh;

use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class KehadiranHariIniTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);

        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
    }

    private function hadir($anak, string $masuk, ?string $pulang = null, ?string $tanggal = null, string $kondisi = 'Sehat dan ceria'): Kehadiran
    {
        $tanggal ??= today()->toDateString();

        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal $masuk:00", 'kondisi_check_in' => $kondisi,
            'check_in_oleh_id' => $this->siti->id, 'check_out_pada' => $pulang ? "$tanggal $pulang:00" : null,
        ]);
    }

    public function test_menampilkan_status_setiap_anak_di_kelompok(): void
    {
        $belum = $this->buatAnak('Anak Belum', $this->batita);
        $hadir = $this->buatAnak('Anak Hadir', $this->batita);
        $pulang = $this->buatAnak('Anak Pulang', $this->batita);
        $this->buatAnak('Anak Kelompok Lain', $this->balita);
        $this->hadir($hadir, '07:45', null, null, 'Batuk ringan');
        $this->hadir($pulang, '08:00', '16:10');

        $this->actingAs($this->siti)
            ->get(route('pengasuh.kehadiran'))
            ->assertOk()
            ->assertSeeInOrder(['Anak Belum', 'Belum datang'])
            ->assertSeeInOrder(['Anak Hadir', 'Hadir', '07:45', 'Batuk ringan'])
            ->assertSeeInOrder(['Anak Pulang', 'Sudah pulang', '08:00', '16:10'])
            ->assertDontSee('Anak Kelompok Lain');

        $this->assertNotNull($belum);
    }

    public function test_kehadiran_hari_lain_tidak_dihitung(): void
    {
        $anak = $this->buatAnak('Anak Kemarin', $this->batita);
        $this->hadir($anak, '07:45', '16:00', today()->subDay()->toDateString());

        $this->actingAs($this->siti)
            ->get(route('pengasuh.kehadiran'))
            ->assertSee('Anak Kemarin')
            ->assertSee('Belum datang')
            ->assertDontSee('Sudah pulang');
    }

    public function test_keadaan_kosong_tanpa_shift(): void
    {
        JadwalShift::query()->delete();
        $this->buatAnak('Anak Batita', $this->batita);

        $this->actingAs($this->siti)
            ->get(route('pengasuh.kehadiran'))
            ->assertSee('Tidak ada anak untuk hari ini')
            ->assertDontSee('Anak Batita');
    }

    public function test_admin_dan_tamu_tidak_bisa_membuka_halaman(): void
    {
        $this->get(route('pengasuh.kehadiran'))->assertRedirect();

        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->get(route('pengasuh.kehadiran'))->assertForbidden();
    }

    public function test_submenu_presensi_memuat_tautan_kehadiran(): void
    {
        $this->actingAs($this->siti)
            ->get(route('dashboard'))
            ->assertSee(route('pengasuh.kehadiran'), false)
            ->assertSee(route('pengasuh.presensi'), false);
    }
}
