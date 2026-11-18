<?php

namespace Tests\Feature\Pengasuh;

use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class JadwalSayaTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private User $dewi;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->siti = User::factory()->create(['nama' => 'Siti Rahmawati'])->assignRole('pengasuh');
        $this->dewi = User::factory()->create(['nama' => 'Dewi Lestari'])->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
    }

    private function shift(User $pengasuh, KelompokUsia $kelompok, int $hari): JadwalShift
    {
        return JadwalShift::create([
            'pengasuh_id' => $pengasuh->id, 'kelompok_usia_id' => $kelompok->id, 'hari' => $hari,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
    }

    public function test_pengasuh_melihat_kelompok_dan_anak_hari_ini(): void
    {
        $this->shift($this->siti, $this->batita, today()->dayOfWeekIso);
        $this->buatAnak('Aisyah Putri', $this->batita);
        $this->buatAnak('Citra Dewi', $this->balita);

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-saya'))
            ->assertOk()
            ->assertSee('Batita')
            ->assertSee('07:00 – 15:00')
            ->assertSee('Aisyah Putri')
            ->assertDontSee('Citra Dewi');
    }

    public function test_hari_tanpa_shift_menampilkan_keadaan_kosong(): void
    {
        $this->shift($this->siti, $this->batita, today()->addDay()->dayOfWeekIso);

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-saya'))
            ->assertOk()
            ->assertSee('Tidak ada shift hari ini');
    }

    public function test_kelompok_tanpa_anak_aktif_diberi_pesan(): void
    {
        $this->shift($this->siti, $this->batita, today()->dayOfWeekIso);
        $this->buatAnak('Dimas', $this->batita, 'nonaktif');

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-saya'))
            ->assertSee('Belum ada anak aktif')
            ->assertDontSee('Dimas');
    }

    public function test_jadwal_pengasuh_lain_tidak_tampil(): void
    {
        $this->shift($this->dewi, $this->balita, today()->dayOfWeekIso);

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-saya'))
            ->assertSee('Belum ada jadwal shift')
            ->assertDontSee('Balita');
    }

    public function test_admin_dan_tamu_tidak_bisa_membuka_halaman(): void
    {
        $this->get(route('pengasuh.jadwal-saya'))->assertRedirect();

        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->get(route('pengasuh.jadwal-saya'))->assertForbidden();
    }

    public function test_menu_jadwal_saya_aktif_untuk_pengasuh(): void
    {
        $this->actingAs($this->siti)
            ->get(route('dashboard'))
            ->assertSee(route('pengasuh.jadwal-saya'), false);
    }
}
