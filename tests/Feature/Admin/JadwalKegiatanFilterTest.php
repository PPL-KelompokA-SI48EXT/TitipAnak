<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalKegiatanIndex;
use App\Models\JadwalKegiatan;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalKegiatanFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);

        $this->kegiatan($this->batita, 'Sarapan Batita', null, 'makan');
        $this->kegiatan($this->batita, 'Senam Senin Batita', 1, 'bermain');
        $this->kegiatan($this->balita, 'Menggambar Balita', 2, 'belajar');
    }

    private function kegiatan(KelompokUsia $k, string $nama, ?int $hari, string $jenis): void
    {
        JadwalKegiatan::create([
            'kelompok_usia_id' => $k->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => $jenis, 'hari' => $hari,
            'jam_mulai' => '08:00:00', 'jam_selesai' => '09:00:00',
        ]);
    }

    public function test_filter_kelompok_usia_hanya_menampilkan_kelompok_itu(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->set('kelompok', (string) $this->balita->id)
            ->assertSee('Menggambar Balita')
            ->assertDontSee('Sarapan Batita')
            ->assertSee('Menampilkan 1 dari 3 kegiatan');
    }

    public function test_filter_hari_memuat_kegiatan_harian_dan_hari_itu(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->set('hari', '1')
            ->assertSee('Sarapan Batita')
            ->assertSee('Senam Senin Batita')
            ->assertDontSee('Menggambar Balita');
    }

    public function test_filter_jenis(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->set('jenis', 'belajar')
            ->assertSee('Menggambar Balita')
            ->assertDontSee('Sarapan Batita')
            ->assertDontSee('Senam Senin Batita');
    }

    public function test_kombinasi_tanpa_hasil_menampilkan_pesan_dan_reset_memulihkan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(JadwalKegiatanIndex::class)
            ->set('kelompok', (string) $this->balita->id)
            ->set('jenis', 'makan')
            ->assertSee('Tidak ada hasil')
            ->call('resetFilter')
            ->assertSet('kelompok', '')
            ->assertSet('jenis', '')
            ->assertSee('Sarapan Batita')
            ->assertSee('Menggambar Balita');
    }

    public function test_filter_terbaca_dari_url(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-kegiatan.index', ['kelompok' => $this->balita->id]))
            ->assertSee('Menggambar Balita')
            ->assertDontSee('Sarapan Batita');
    }
}
