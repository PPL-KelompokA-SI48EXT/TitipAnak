<?php

namespace Tests\Feature;

use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class KehadiranModelTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private KelompokUsia $batita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siti = User::factory()->create();
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
    }

    private function hadir(string $tanggal, array $tambahan = [], ?string $nama = 'Aisyah'): Kehadiran
    {
        $anak = $this->buatAnak($nama, $this->batita);

        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => $tanggal.' 07:45:00',
            'kondisi_check_in' => 'Sehat', 'check_in_oleh_id' => $this->siti->id,
        ] + $tambahan);
    }

    public function test_relasi_dan_cast_tanggal(): void
    {
        $k = $this->hadir('2026-11-16');

        $this->assertInstanceOf(Carbon::class, $k->tanggal);
        $this->assertInstanceOf(Carbon::class, $k->check_in_pada);
        $this->assertSame('Aisyah', $k->anak->nama_lengkap);
        $this->assertSame($this->siti->id, $k->checkInOleh->id);
        $this->assertCount(1, $k->anak->kehadiran);
    }

    public function test_status_hadir_lalu_pulang_setelah_check_out(): void
    {
        $k = $this->hadir('2026-11-16');
        $this->assertSame(Kehadiran::STATUS_HADIR, $k->status());
        $this->assertFalse($k->sudahPulang());

        $k->update(['check_out_pada' => '2026-11-16 16:00:00', 'check_out_oleh_id' => $this->siti->id, 'nama_penjemput' => 'Ibu Aisyah']);

        $this->assertSame(Kehadiran::STATUS_PULANG, $k->fresh()->status());
        $this->assertTrue($k->fresh()->sudahPulang());
    }

    public function test_scope_tanggal_dan_masih_di_tempat(): void
    {
        $hariIni = $this->hadir('2026-11-16', [], 'Aisyah');
        $this->hadir('2026-11-15', [], 'Bima');
        $pulang = $this->hadir('2026-11-16', ['check_out_pada' => '2026-11-16 16:00:00'], 'Citra');

        $ids = Kehadiran::padaTanggal(Carbon::parse('2026-11-16'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$hariIni->id, $pulang->id], $ids);

        $this->assertSame([$hariIni->id], Kehadiran::padaTanggal(Carbon::parse('2026-11-16'))->masihDiTempat()->pluck('id')->all());
    }

    public function test_satu_anak_hanya_boleh_satu_kehadiran_per_hari(): void
    {
        $k = $this->hadir('2026-11-16');

        $this->expectException(QueryException::class);

        Kehadiran::create([
            'anak_id' => $k->anak_id, 'tanggal' => '2026-11-16', 'check_in_pada' => '2026-11-16 09:00:00',
            'check_in_oleh_id' => $this->siti->id,
        ]);
    }
}
