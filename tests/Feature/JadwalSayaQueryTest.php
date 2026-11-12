<?php

namespace Tests\Feature;

use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class JadwalSayaQueryTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private User $dewi;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siti = User::factory()->create(['nama' => 'Siti']);
        $this->dewi = User::factory()->create(['nama' => 'Dewi']);
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
    }

    private function shift(User $pengasuh, KelompokUsia $kelompok, int $hari, string $mulai = '07:00'): JadwalShift
    {
        return JadwalShift::create([
            'pengasuh_id' => $pengasuh->id, 'kelompok_usia_id' => $kelompok->id, 'hari' => $hari,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => '15:00:00',
        ]);
    }

    public function test_hanya_shift_milik_pengasuh_pada_hari_itu(): void
    {
        $senin = Carbon::parse('2026-11-16'); // Senin
        $this->shift($this->siti, $this->batita, 1);
        $this->shift($this->siti, $this->batita, 2);
        $this->shift($this->dewi, $this->balita, 1);

        $hasil = JadwalShift::tanggungJawab($this->siti, $senin);

        $this->assertCount(1, $hasil);
        $this->assertSame($this->siti->id, $hasil->first()->pengasuh_id);
        $this->assertSame(1, $hasil->first()->hari);
    }

    public function test_memuat_anak_aktif_di_kelompok_usia_shift(): void
    {
        $this->shift($this->siti, $this->batita, 1);
        $this->buatAnak('Aisyah', $this->batita);
        $this->buatAnak('Bima', $this->batita);
        $this->buatAnak('Citra', $this->balita);
        $this->buatAnak('Dimas', $this->batita, 'nonaktif');
        $this->buatAnak('Eka', $this->batita, 'menunggu');
        $this->buatAnak('Fajar', $this->batita)->delete();

        $anak = JadwalShift::tanggungJawab($this->siti, Carbon::parse('2026-11-16'))
            ->first()->kelompokUsia->anak->pluck('nama_lengkap')->all();

        $this->assertSame(['Aisyah', 'Bima'], $anak);
    }

    public function test_hari_tanpa_shift_mengembalikan_koleksi_kosong(): void
    {
        $this->shift($this->siti, $this->batita, 1);

        $this->assertTrue(JadwalShift::tanggungJawab($this->siti, Carbon::parse('2026-11-18'))->isEmpty());
    }

    public function test_beberapa_shift_sehari_diurutkan_menurut_jam(): void
    {
        $siang = $this->shift($this->siti, $this->balita, 3, '12:00');
        $pagi = $this->shift($this->siti, $this->batita, 3, '07:00');

        $ids = JadwalShift::tanggungJawab($this->siti, Carbon::parse('2026-11-18'))->pluck('id')->all();

        $this->assertSame([$pagi->id, $siang->id], $ids);
    }

    public function test_pola_mingguan_urut_hari_lalu_jam(): void
    {
        $rabu = $this->shift($this->siti, $this->batita, 3);
        $senin = $this->shift($this->siti, $this->batita, 1);
        $this->shift($this->dewi, $this->batita, 2);

        $this->assertSame([$senin->id, $rabu->id], JadwalShift::polaMingguan($this->siti)->pluck('id')->all());
    }
}
