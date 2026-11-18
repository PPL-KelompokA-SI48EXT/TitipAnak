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

class CheckInSimpanTest extends TestCase
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
        // Waktu dibekukan di tengah hari kerja agar jam datang uji tidak "di masa depan".
        Carbon::setTestNow(today()->setTime(10, 30));

        $this->siti = User::factory()->create(['nama' => 'Siti'])->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);

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

    private function form(int $anakId, string $jam = '07:45', string $kondisi = 'Sehat dan ceria')
    {
        return Livewire::actingAs($this->siti)
            ->test(Presensi::class)
            ->call('mulaiCheckIn', $anakId)
            ->set('jam_datang', $jam)
            ->set('kondisi', $kondisi);
    }

    public function test_check_in_tersimpan_dengan_jam_dan_kondisi(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        $this->form($anak->id, '07:45', '  Batuk ringan  ')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSet('anakId', null)
            ->assertSee('Check-in Aisyah Putri berhasil dicatat.')
            ->assertSee('Jam datang')
            ->assertSee('07:45');

        $this->assertDatabaseHas('kehadiran', [
            'anak_id' => $anak->id, 'kondisi_check_in' => 'Batuk ringan', 'check_in_oleh_id' => $this->siti->id,
            'check_out_pada' => null,
        ]);
        $this->assertSame('07:45', Kehadiran::first()->check_in_pada->format('H:i'));
        $this->assertTrue(Kehadiran::first()->tanggal->isToday());
    }

    public function test_jam_datang_tidak_boleh_melewati_jam_sekarang(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        $this->form($anak->id, '10:31')->call('simpan')->assertHasErrors(['jam_datang']);
        $this->form($anak->id, '10:30')->call('simpan')->assertHasNoErrors();
    }

    public function test_format_jam_dan_kondisi_divalidasi(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        $this->form($anak->id, 'pagi', str_repeat('a', 256))
            ->call('simpan')
            ->assertHasErrors(['jam_datang', 'kondisi']);

        $this->assertDatabaseCount('kehadiran', 0);
    }

    public function test_hanya_anak_di_kelompok_yang_dipegang_yang_bisa_disimpan(): void
    {
        $asing = $this->buatAnak('Citra Dewi', $this->balita);
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        $komponen = $this->form($anak->id);
        // Shift pengasuh dihapus setelah form dibuka: simpan harus ditolak dengan pesan, bukan error.
        JadwalShift::query()->delete();
        $komponen->call('simpan')->assertSee('Anak tidak ditemukan di kelompok Anda hari ini.');

        $this->assertDatabaseCount('kehadiran', 0);
        $this->assertNotNull($asing);
    }

    public function test_pengguna_tanpa_izin_tidak_boleh_menyimpan(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        Livewire::actingAs($admin)->test(Presensi::class)->assertForbidden();
    }
}
