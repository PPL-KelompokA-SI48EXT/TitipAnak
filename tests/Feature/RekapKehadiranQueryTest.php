<?php

namespace Tests\Feature;

use App\Models\Anak;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class RekapKehadiranQueryTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    private Anak $aisyah;

    private Anak $bima;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siti = User::factory()->create();
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
        $this->aisyah = $this->buatAnak('Aisyah Putri', $this->batita);
        $this->bima = $this->buatAnak('Bima Pratama', $this->balita);
    }

    private function hadir(Anak $anak, string $tanggal, string $masuk = '07:30', ?string $pulang = '16:00'): Kehadiran
    {
        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal $masuk:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $this->siti->id, 'check_out_pada' => $pulang ? "$tanggal $pulang:00" : null,
        ]);
    }

    public function test_tanpa_filter_mengembalikan_semua_urut_tanggal_terbaru(): void
    {
        $lama = $this->hadir($this->aisyah, '2026-11-02');
        $baru = $this->hadir($this->aisyah, '2026-11-10');
        $tengah = $this->hadir($this->bima, '2026-11-05');

        $this->assertSame([$baru->id, $tengah->id, $lama->id], Kehadiran::rekap()->pluck('id')->all());
    }

    public function test_filter_rentang_tanggal_inklusif(): void
    {
        $this->hadir($this->aisyah, '2026-11-01');
        $awal = $this->hadir($this->aisyah, '2026-11-02');
        $akhir = $this->hadir($this->aisyah, '2026-11-04');
        $this->hadir($this->aisyah, '2026-11-05');

        $ids = Kehadiran::rekap(['dari' => Carbon::parse('2026-11-02'), 'sampai' => Carbon::parse('2026-11-04')])->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$awal->id, $akhir->id], $ids);
    }

    public function test_salah_satu_sisi_tanggal_boleh_kosong(): void
    {
        $this->hadir($this->aisyah, '2026-11-01');
        $b = $this->hadir($this->aisyah, '2026-11-09');

        $this->assertSame([$b->id], Kehadiran::rekap(['dari' => Carbon::parse('2026-11-05')])->pluck('id')->all());
        $this->assertCount(1, Kehadiran::rekap(['sampai' => Carbon::parse('2026-11-05')])->get());
    }

    public function test_filter_kelompok_usia_dan_nama_anak(): void
    {
        $a = $this->hadir($this->aisyah, '2026-11-02');
        $b = $this->hadir($this->bima, '2026-11-02');

        $this->assertSame([$a->id], Kehadiran::rekap(['kelompok' => $this->batita->id])->pluck('id')->all());
        $this->assertSame([$b->id], Kehadiran::rekap(['q' => ' pratama '])->pluck('id')->all());
        $this->assertSame([], Kehadiran::rekap(['kelompok' => $this->balita->id, 'q' => 'Aisyah'])->pluck('id')->all());
    }

    public function test_anak_yang_dihapus_lunak_tetap_ada_di_rekap(): void
    {
        $k = $this->hadir($this->aisyah, '2026-11-02');
        $this->aisyah->delete();

        $hasil = Kehadiran::rekap()->first();

        $this->assertSame($k->id, $hasil->id);
        $this->assertSame('Aisyah Putri', $hasil->anak->nama_lengkap);
    }

    public function test_dalam_satu_hari_diurutkan_menurut_jam_datang(): void
    {
        $siang = $this->hadir($this->aisyah, '2026-11-02', '10:00');
        $pagi = $this->hadir($this->bima, '2026-11-02', '07:15');

        $this->assertSame([$pagi->id, $siang->id], Kehadiran::rekap()->pluck('id')->all());
    }
}
