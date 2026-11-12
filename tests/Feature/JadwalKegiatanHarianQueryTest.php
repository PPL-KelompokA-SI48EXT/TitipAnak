<?php

namespace Tests\Feature;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalKegiatanHarianQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $siti;

    private User $dewi;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siti = User::factory()->create();
        $this->dewi = User::factory()->create();
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
    }

    private function shift(User $p, KelompokUsia $k, int $hari): void
    {
        JadwalShift::create([
            'pengasuh_id' => $p->id, 'kelompok_usia_id' => $k->id, 'hari' => $hari,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
    }

    private function kegiatan(KelompokUsia $k, string $nama, ?int $hari, string $mulai): JadwalKegiatan
    {
        return JadwalKegiatan::create([
            'kelompok_usia_id' => $k->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => 'bermain', 'hari' => $hari,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => '23:00:00',
        ]);
    }

    public function test_hanya_kegiatan_kelompok_yang_dipegang_pada_hari_itu(): void
    {
        $senin = Carbon::parse('2026-11-16');
        $this->shift($this->siti, $this->batita, 1);
        $this->shift($this->dewi, $this->balita, 1);
        $this->kegiatan($this->batita, 'Sarapan Batita', null, '07:30');
        $this->kegiatan($this->batita, 'Senam Senin', 1, '08:00');
        $this->kegiatan($this->batita, 'Mewarnai Selasa', 2, '08:00');
        $this->kegiatan($this->balita, 'Kegiatan Balita', null, '08:00');

        $hasil = JadwalKegiatan::harianUntukPengasuh($this->siti, $senin);

        $this->assertSame([$this->batita->id], $hasil->keys()->all());
        $this->assertSame(['Sarapan Batita', 'Senam Senin'], $hasil->first()->pluck('nama_kegiatan')->all());
    }

    public function test_tanpa_shift_hari_itu_hasilnya_kosong(): void
    {
        $this->shift($this->siti, $this->batita, 2);
        $this->kegiatan($this->batita, 'Sarapan', null, '07:30');

        $this->assertTrue(JadwalKegiatan::harianUntukPengasuh($this->siti, Carbon::parse('2026-11-16'))->isEmpty());
    }

    public function test_dua_shift_di_kelompok_berbeda_dikelompokkan_dan_diurutkan_jam(): void
    {
        $this->shift($this->siti, $this->batita, 3);
        $this->shift($this->siti, $this->balita, 3);
        $siang = $this->kegiatan($this->batita, 'Tidur siang', null, '12:30');
        $pagi = $this->kegiatan($this->batita, 'Sarapan', null, '07:30');
        $this->kegiatan($this->balita, 'Bermain', null, '09:00');

        $hasil = JadwalKegiatan::harianUntukPengasuh($this->siti, Carbon::parse('2026-11-18'));

        $this->assertEqualsCanonicalizing([$this->batita->id, $this->balita->id], $hasil->keys()->all());
        $this->assertSame([$pagi->id, $siang->id], $hasil->get($this->batita->id)->pluck('id')->all());
    }

    public function test_dua_shift_di_kelompok_sama_tidak_menggandakan_kegiatan(): void
    {
        $this->shift($this->siti, $this->batita, 1);
        $this->shift($this->siti, $this->batita, 1);
        $this->kegiatan($this->batita, 'Sarapan', null, '07:30');

        $hasil = JadwalKegiatan::harianUntukPengasuh($this->siti, Carbon::parse('2026-11-16'));

        $this->assertCount(1, $hasil->get($this->batita->id));
    }
}
