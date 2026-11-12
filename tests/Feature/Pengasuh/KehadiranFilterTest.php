<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\KehadiranHariIni;
use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class KehadiranFilterTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);

        $this->buatAnak('Aisyah Belum', $batita);
        $this->absen($this->buatAnak('Bima Hadir', $batita), null);
        $this->absen($this->buatAnak('Citra Pulang', $batita), '16:00');
    }

    private function absen($anak, ?string $pulang): void
    {
        $tanggal = today()->toDateString();

        Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal 07:50:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $this->siti->id, 'check_out_pada' => $pulang ? "$tanggal $pulang:00" : null,
        ]);
    }

    public function test_ringkasan_jumlah_per_status(): void
    {
        Livewire::actingAs($this->siti)
            ->test(KehadiranHariIni::class)
            ->assertSee('Belum datang · 1')
            ->assertSee('Hadir · 1')
            ->assertSee('Sudah pulang · 1')
            ->assertSee('Menampilkan 3 dari 3 anak');
    }

    public function test_filter_status_hanya_menampilkan_status_itu(): void
    {
        Livewire::actingAs($this->siti)
            ->test(KehadiranHariIni::class)
            ->call('pilihStatus', 'hadir')
            ->assertSet('status', 'hadir')
            ->assertSee('Bima Hadir')
            ->assertDontSee('Aisyah Belum')
            ->assertDontSee('Citra Pulang')
            ->assertSee('Menampilkan 1 dari 3 anak');
    }

    public function test_klik_status_yang_sama_lagi_mematikan_filter(): void
    {
        Livewire::actingAs($this->siti)
            ->test(KehadiranHariIni::class)
            ->call('pilihStatus', 'pulang')
            ->call('pilihStatus', 'pulang')
            ->assertSet('status', '')
            ->assertSee('Aisyah Belum');
    }

    public function test_status_tidak_dikenal_diabaikan(): void
    {
        Livewire::actingAs($this->siti)
            ->test(KehadiranHariIni::class)
            ->call('pilihStatus', 'asal')
            ->assertSet('status', '')
            ->assertSee('Menampilkan 3 dari 3 anak');
    }

    public function test_cari_nama_tanpa_memperhatikan_huruf_besar_kecil(): void
    {
        Livewire::actingAs($this->siti)
            ->test(KehadiranHariIni::class)
            ->set('q', 'cItRa')
            ->assertSee('Citra Pulang')
            ->assertDontSee('Bima Hadir');
    }

    public function test_kombinasi_tanpa_hasil_dan_reset(): void
    {
        Livewire::actingAs($this->siti)
            ->test(KehadiranHariIni::class)
            ->call('pilihStatus', 'hadir')
            ->set('q', 'Aisyah')
            ->assertSee('Tidak ada hasil')
            ->call('resetFilter')
            ->assertSet('status', '')
            ->assertSet('q', '')
            ->assertSee('Aisyah Belum');
    }

    public function test_filter_terbaca_dari_url(): void
    {
        $this->actingAs($this->siti)
            ->get(route('pengasuh.kehadiran', ['status' => 'pulang']))
            ->assertSee('Citra Pulang')
            ->assertDontSee('Bima Hadir');
    }
}
