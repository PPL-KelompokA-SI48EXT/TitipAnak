<?php

namespace Tests\Feature;

use App\Livewire\Pengasuh\Presensi;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

/** Aplikasi dipakai di WIB: "hari ini" dan jam default harus mengikuti WIB, bukan UTC. */
class ZonaWaktuTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private KelompokUsia $batita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_zona_waktu_aplikasi_adalah_wib(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('+07:00', now()->format('P'));
    }

    public function test_dini_hari_wib_sudah_dihitung_sebagai_hari_baru(): void
    {
        // Senin 16 Nov 2026 pukul 01.00 WIB = Minggu 15 Nov 18.00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-11-16 01:00:00', 'Asia/Jakarta'));
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => 1,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
        $this->buatAnak('Aisyah Putri', $this->batita);

        $this->assertSame(1, today()->dayOfWeekIso);

        $this->actingAs($this->siti)
            ->get(route('pengasuh.jadwal-saya'))
            ->assertSee('Batita')
            ->assertDontSee('Tidak ada shift hari ini');

        $this->actingAs($this->siti)
            ->get(route('pengasuh.presensi'))
            ->assertSee('Aisyah Putri');
    }

    public function test_jam_default_form_check_in_memakai_jam_wib(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 08:15:00', 'Asia/Jakarta'));
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => 1,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        Livewire::actingAs($this->siti)
            ->test(Presensi::class)
            ->call('mulaiCheckIn', $anak->id)
            ->assertSet('jam_datang', '08:15');
    }
}
