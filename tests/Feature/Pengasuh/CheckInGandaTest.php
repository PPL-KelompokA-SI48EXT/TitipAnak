<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\Presensi;
use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class CheckInGandaTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private KelompokUsia $batita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow(today()->setTime(10, 30));

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function catat($anak, string $tanggal, string $jam = '07:45'): Kehadiran
    {
        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal $jam:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $this->siti->id,
        ]);
    }

    public function test_anak_yang_sudah_check_in_tidak_bisa_membuka_form_lagi(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);
        $this->catat($anak, today()->toDateString());

        Livewire::actingAs($this->siti)
            ->test(Presensi::class)
            ->call('mulaiCheckIn', $anak->id)
            ->assertSet('anakId', null)
            ->assertSee('Aisyah Putri sudah check-in hari ini pukul 07:45.')
            ->assertSee('Check-in tidak bisa dicatat dua kali.');
    }

    public function test_pesan_error_tampil_saat_simpan_ganda_dan_hilang_di_aksi_berikutnya(): void
    {
        $anak = $this->buatAnak('Bima Hadir', $this->batita);
        $lain = $this->buatAnak('Citra Baru', $this->batita);

        $komponen = Livewire::actingAs($this->siti)->test(Presensi::class)
            ->call('mulaiCheckIn', $anak->id)
            ->set('jam_datang', '07:45')
            ->set('kondisi', 'Sehat');
        $this->catat($anak, today()->toDateString(), '07:40');

        $komponen->call('simpan')
            ->assertSee('Tidak bisa dicatat.')
            ->assertSee('Bima Hadir sudah check-in hari ini pukul 07:40.')
            ->assertDontSee('berhasil dicatat');

        $this->assertNotNull($lain);
    }

    public function test_tanpa_duplikasi_tidak_ada_pesan_error(): void
    {
        $anak = $this->buatAnak('Dimas Baru', $this->batita);

        Livewire::actingAs($this->siti)->test(Presensi::class)
            ->call('mulaiCheckIn', $anak->id)
            ->set('jam_datang', '07:45')
            ->set('kondisi', 'Sehat')
            ->call('simpan')
            ->assertDontSee('Tidak bisa dicatat.')
            ->assertSee('Check-in Dimas Baru berhasil dicatat.');
    }

    public function test_simpan_kedua_pada_hari_yang_sama_tidak_membuat_baris_baru(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        $komponen = Livewire::actingAs($this->siti)->test(Presensi::class)
            ->call('mulaiCheckIn', $anak->id)
            ->set('jam_datang', '07:45')
            ->set('kondisi', 'Sehat');

        // Pengasuh lain mencatat anak yang sama setelah form ini dibuka.
        $this->catat($anak, today()->toDateString(), '07:40');

        $komponen->call('simpan')->assertSet('anakId', null);

        $this->assertDatabaseCount('kehadiran', 1);
        $this->assertSame('07:40', Kehadiran::first()->check_in_pada->format('H:i'));
    }

    public function test_kehadiran_hari_lain_tidak_menghalangi_check_in_hari_ini(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);
        $this->catat($anak, today()->subDay()->toDateString());

        Livewire::actingAs($this->siti)->test(Presensi::class)
            ->call('mulaiCheckIn', $anak->id)
            ->assertSet('anakId', $anak->id)
            ->set('jam_datang', '07:45')
            ->set('kondisi', 'Sehat')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('kehadiran', 2);
    }

    public function test_database_menolak_baris_ganda_untuk_anak_dan_tanggal_yang_sama(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);
        $this->catat($anak, today()->toDateString());

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $this->catat($anak, today()->toDateString(), '09:00');
    }
}
