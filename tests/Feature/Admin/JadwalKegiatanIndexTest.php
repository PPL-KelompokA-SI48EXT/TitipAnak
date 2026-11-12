<?php

namespace Tests\Feature\Admin;

use App\Models\JadwalKegiatan;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalKegiatanIndexTest extends TestCase
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
    }

    private function kegiatan(KelompokUsia $k, string $nama, ?int $hari = null, string $mulai = '08:00', string $selesai = '09:00', string $jenis = 'bermain'): JadwalKegiatan
    {
        return JadwalKegiatan::create([
            'kelompok_usia_id' => $k->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => $jenis, 'hari' => $hari,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00',
        ]);
    }

    public function test_admin_melihat_kegiatan_per_kelompok_usia(): void
    {
        $this->kegiatan($this->batita, 'Sarapan Batita', null, '07:30', '08:00', 'makan');
        $this->kegiatan($this->balita, 'Senam Balita', 2);

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-kegiatan.index'))
            ->assertOk()
            ->assertSeeInOrder(['Batita', 'Sarapan Batita', 'Balita', 'Senam Balita'])
            ->assertSee('07:30 – 08:00')
            ->assertSee('Setiap hari')
            ->assertSee('Selasa')
            ->assertSee('Makan');
    }

    public function test_kegiatan_diurutkan_setiap_hari_dulu_lalu_hari_dan_jam(): void
    {
        $this->kegiatan($this->batita, 'Kegiatan Rabu', 3, '08:00', '09:00');
        $this->kegiatan($this->batita, 'Kegiatan Senin Siang', 1, '13:00', '14:00');
        $this->kegiatan($this->batita, 'Kegiatan Senin Pagi', 1, '08:00', '09:00');
        $this->kegiatan($this->batita, 'Kegiatan Harian', null, '15:00', '16:00');

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-kegiatan.index'))
            ->assertSeeInOrder(['Kegiatan Harian', 'Kegiatan Senin Pagi', 'Kegiatan Senin Siang', 'Kegiatan Rabu']);
    }

    public function test_kelompok_tanpa_kegiatan_diberi_keterangan(): void
    {
        $this->kegiatan($this->batita, 'Sarapan');

        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-kegiatan.index'))
            ->assertSee('Belum ada kegiatan untuk kelompok usia ini.');
    }

    public function test_keadaan_kosong_saat_belum_ada_kegiatan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.jadwal-kegiatan.index'))
            ->assertOk()
            ->assertSee('Belum ada jadwal kegiatan');
    }

    public function test_pengasuh_dan_tamu_tidak_bisa_membuka_halaman(): void
    {
        $this->get(route('admin.jadwal-kegiatan.index'))->assertRedirect();

        $pengasuh = User::factory()->create()->assignRole('pengasuh');
        $this->actingAs($pengasuh)->get(route('admin.jadwal-kegiatan.index'))->assertForbidden();
    }

    public function test_menu_admin_memuat_tautan_lihat_jadwal_kegiatan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertSee(route('admin.jadwal-kegiatan.index'), false);
    }
}
