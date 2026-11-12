<?php

namespace Tests\Feature;

use App\Models\JadwalKegiatan;
use App\Models\KelompokUsia;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalKegiatanModelTest extends TestCase
{
    use RefreshDatabase;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
    }

    private function kegiatan(KelompokUsia $k, string $nama, ?int $hari, string $mulai, string $selesai, string $jenis = 'bermain'): JadwalKegiatan
    {
        return JadwalKegiatan::create([
            'kelompok_usia_id' => $k->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => $jenis, 'hari' => $hari,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00',
        ]);
    }

    public function test_kegiatan_terhubung_ke_kelompok_usia(): void
    {
        $this->kegiatan($this->batita, 'Sarapan', null, '07:30', '08:00', 'makan');

        $this->assertSame('Sarapan', $this->batita->jadwalKegiatan()->first()->nama_kegiatan);
        $this->assertSame('Batita', JadwalKegiatan::first()->kelompokUsia->nama);
    }

    public function test_scope_pada_tanggal_memuat_kegiatan_harian_dan_hari_itu_saja(): void
    {
        $setiapHari = $this->kegiatan($this->batita, 'Sarapan', null, '07:30', '08:00');
        $senin = $this->kegiatan($this->batita, 'Senam', 1, '09:00', '09:30');
        $selasa = $this->kegiatan($this->batita, 'Mewarnai', 2, '09:00', '09:30');

        $ids = JadwalKegiatan::padaTanggal(Carbon::parse('2026-11-16'))->pluck('id')->all(); // Senin

        $this->assertEqualsCanonicalizing([$setiapHari->id, $senin->id], $ids);
        $this->assertNotContains($selasa->id, $ids);
    }

    public function test_scope_bertabrakan_mengikuti_kelompok_hari_dan_jam(): void
    {
        $this->kegiatan($this->batita, 'Bermain', 1, '08:00', '09:00');

        $this->assertTrue(JadwalKegiatan::bertabrakan($this->batita->id, 1, '08:30', '09:30')->exists());
        $this->assertTrue(JadwalKegiatan::bertabrakan($this->batita->id, null, '08:30', '09:30')->exists());
        // Menempel (09:00 = selesai kegiatan lain) bukan tabrakan.
        $this->assertFalse(JadwalKegiatan::bertabrakan($this->batita->id, 1, '09:00', '10:00')->exists());
        $this->assertFalse(JadwalKegiatan::bertabrakan($this->batita->id, 2, '08:30', '09:30')->exists());
        $this->assertFalse(JadwalKegiatan::bertabrakan($this->balita->id, 1, '08:30', '09:30')->exists());
    }

    public function test_label_hari_dan_jenis(): void
    {
        $harian = $this->kegiatan($this->batita, 'Sarapan', null, '07:30', '08:00', 'makan');
        $rabu = $this->kegiatan($this->batita, 'Senam', 3, '09:00', '09:30', 'tidak-dikenal');

        $this->assertSame('Setiap hari', $harian->labelHari());
        $this->assertSame('Makan', $harian->labelJenis());
        $this->assertSame('Rabu', $rabu->labelHari());
        $this->assertSame('Tidak-dikenal', $rabu->labelJenis());
    }
}
