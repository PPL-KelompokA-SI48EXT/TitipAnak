<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RekapKehadiran;
use App\Models\Anak;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class RekapKehadiranTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $admin;

    private User $siti;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    private Anak $aisyah;

    private Anak $bima;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
        $this->aisyah = $this->buatAnak('Aisyah Putri', $this->batita);
        $this->bima = $this->buatAnak('Bima Pratama', $this->balita);
    }

    private function hadir(Anak $anak, string $tanggal, ?string $pulang = '16:00', ?string $penjemput = 'Ibu Aisyah'): Kehadiran
    {
        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal 07:30:00", 'kondisi_check_in' => 'Batuk ringan',
            'check_in_oleh_id' => $this->siti->id, 'check_out_pada' => $pulang ? "$tanggal $pulang:00" : null,
            'check_out_oleh_id' => $pulang ? $this->siti->id : null, 'nama_penjemput' => $pulang ? $penjemput : null,
        ]);
    }

    public function test_bawaan_menampilkan_bulan_ini_sampai_hari_ini(): void
    {
        $this->hadir($this->aisyah, today()->toDateString());
        $this->hadir($this->bima, today()->startOfMonth()->toDateString());
        $this->hadir($this->bima, today()->startOfMonth()->subDay()->toDateString());

        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->assertSee('Aisyah Putri')
            ->assertSee('Bima Pratama')
            ->assertSee('2 catatan kehadiran');
    }

    public function test_menampilkan_detail_setiap_catatan(): void
    {
        $this->hadir($this->aisyah, '2026-11-02');
        $this->hadir($this->bima, '2026-11-02', null);

        $this->actingAs($this->admin)
            ->get(route('admin.kehadiran.rekap', ['dari' => '2026-11-01', 'sampai' => '2026-11-30']))
            ->assertOk()
            ->assertSee('02/11/2026')
            ->assertSee('Batita')
            ->assertSee('07:30')
            ->assertSee('Batuk ringan')
            ->assertSee('16:00')
            ->assertSee('Ibu Aisyah')
            ->assertSee('Belum pulang');
    }

    public function test_filter_rentang_tanggal(): void
    {
        $this->hadir($this->aisyah, '2026-11-02');
        $this->hadir($this->bima, '2026-11-20');

        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->set('dari', '2026-11-01')
            ->set('sampai', '2026-11-10')
            ->assertSee('Aisyah Putri')
            ->assertDontSee('Bima Pratama')
            ->assertSee('1 catatan kehadiran');
    }

    public function test_filter_kelompok_dan_nama(): void
    {
        $this->hadir($this->aisyah, '2026-11-02');
        $this->hadir($this->bima, '2026-11-02');

        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->set('dari', '2026-11-01')->set('sampai', '2026-11-30')
            ->set('kelompok', (string) $this->balita->id)
            ->assertSee('Bima Pratama')
            ->assertDontSee('Aisyah Putri')
            ->set('kelompok', '')
            ->set('q', 'aisyah')
            ->assertSee('Aisyah Putri')
            ->assertDontSee('Bima Pratama');
    }

    public function test_rentang_terbalik_memberi_peringatan(): void
    {
        $this->hadir($this->aisyah, '2026-11-02');

        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->set('dari', '2026-11-30')
            ->set('sampai', '2026-11-01')
            ->assertSee('Tanggal mulai lebih akhir dari tanggal selesai')
            ->assertSee('Tidak ada data kehadiran');
    }

    public function test_tanggal_tidak_valid_diabaikan_dan_memakai_bawaan(): void
    {
        $this->hadir($this->aisyah, today()->toDateString());

        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->set('dari', 'bukan-tanggal')
            ->assertSee('Aisyah Putri');
    }

    public function test_reset_filter_dan_keadaan_kosong(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->set('q', 'tidak ada anak ini')
            ->assertSee('Tidak ada data kehadiran')
            ->call('resetFilter')
            ->assertSet('q', '')
            ->assertSet('dari', '');
    }

    public function test_pagination_membatasi_jumlah_baris_per_halaman(): void
    {
        for ($i = 1; $i <= RekapKehadiran::PER_HALAMAN + 3; $i++) {
            $this->hadir($this->buatAnak(sprintf('Anak Nomor %02d', $i), $this->batita), today()->toDateString());
        }

        $komponen = Livewire::actingAs($this->admin)->test(RekapKehadiran::class);
        $komponen->assertSee('23 catatan kehadiran');
        $this->assertSame(RekapKehadiran::PER_HALAMAN, $komponen->viewData('baris')->count());

        $komponen->call('setPage', 2);
        $this->assertSame(3, $komponen->viewData('baris')->count());
    }

    public function test_ringkasan_per_anak_menampilkan_agregat(): void
    {
        $this->hadir($this->aisyah, '2026-11-02', '16:00');
        $this->hadir($this->aisyah, '2026-11-03', null);
        $this->hadir($this->bima, '2026-11-02');

        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->set('dari', '2026-11-01')->set('sampai', '2026-11-30')
            ->call('setTampilan', 'anak')
            ->assertSet('tampilan', 'anak')
            ->assertSee('2 anak · 3 catatan kehadiran')
            ->assertSeeInOrder(['Aisyah Putri', 'Batita', '2 hari', '07:30', '8 j 30 m', '1 hari'])
            ->assertSeeInOrder(['Bima Pratama', 'Balita', '1 hari'])
            ->assertSee('Rata-rata di tempat');
    }

    public function test_ringkasan_mengikuti_filter_dan_menampilkan_keadaan_kosong(): void
    {
        $this->hadir($this->aisyah, '2026-11-02');
        $this->hadir($this->bima, '2026-11-02');

        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->set('dari', '2026-11-01')->set('sampai', '2026-11-30')
            ->call('setTampilan', 'anak')
            ->set('kelompok', (string) $this->balita->id)
            ->assertSee('Bima Pratama')
            ->assertDontSee('Aisyah Putri')
            ->set('q', 'tidak ada')
            ->assertSee('Tidak ada data kehadiran');
    }

    public function test_tampilan_tidak_dikenal_kembali_ke_per_catatan_dan_terbaca_dari_url(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->call('setTampilan', 'asal')
            ->assertSet('tampilan', 'catatan');

        $this->hadir($this->aisyah, today()->toDateString());
        $this->actingAs($this->admin)
            ->get(route('admin.kehadiran.rekap', ['tampilan' => 'anak']))
            ->assertSee('Hari hadir')
            ->assertSee('1 anak · 1 catatan kehadiran');
    }

    public function test_pengasuh_orang_tua_dan_tamu_tidak_bisa_membuka_halaman(): void
    {
        $this->get(route('admin.kehadiran.rekap'))->assertRedirect();
        $this->actingAs($this->siti)->get(route('admin.kehadiran.rekap'))->assertForbidden();

        $ortu = User::factory()->create()->assignRole('orang_tua');
        $this->actingAs($ortu)->get(route('admin.kehadiran.rekap'))->assertForbidden();
    }

    public function test_menu_admin_memuat_tautan_rekap(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertSee(route('admin.kehadiran.rekap'), false);
    }
}
