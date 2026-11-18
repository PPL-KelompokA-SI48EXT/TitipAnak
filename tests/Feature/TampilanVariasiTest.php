<?php

namespace Tests\Feature;

use App\Livewire\Pengasuh\Presensi;
use App\Livewire\Admin\JadwalKegiatanIndex;
use App\Livewire\Admin\RekapKehadiran;
use App\Livewire\Pengasuh\JadwalSaya;
use App\Models\JadwalKegiatan;
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

/**
 * Tampilan kartu, garis waktu, strip mingguan, dan kartu ringkasan pada halaman PBI-14, 15, dan 16.
 */
class TampilanVariasiTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private User $admin;

    private KelompokUsia $batita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        config(['titipanak.dev_switcher' => false]);
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:30:00')); // Rabu

        $this->siti = User::factory()->create(['nama' => 'Siti Pengasuh'])->assignRole('pengasuh');
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);

        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => 3,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function hadir($anak, string $masuk, ?string $pulang = null): Kehadiran
    {
        $tgl = today()->toDateString();

        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tgl, 'check_in_pada' => "$tgl $masuk:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $this->siti->id, 'check_out_pada' => $pulang ? "$tgl $pulang:00" : null,
        ]);
    }

    public function test_kartu_kehadiran_menampilkan_tombol_aksi_sesuai_status(): void
    {
        $belum = $this->buatAnak('Anak Belum', $this->batita);
        $hadir = $this->buatAnak('Anak Hadir', $this->batita);
        $pulang = $this->buatAnak('Anak Pulang', $this->batita);
        $this->hadir($hadir, '07:45');
        $this->hadir($pulang, '08:00', '15:00');

        $this->actingAs($this->siti);
        Livewire::test(Presensi::class)
            ->assertSee('Check-in Anak Belum')
            ->assertSee('Check-out Anak Hadir')
            ->assertDontSee('Check-in Anak Hadir')
            ->assertDontSee('Check-out Anak Pulang')
            ->assertDontSee('Check-in Anak Pulang');
    }

    public function test_tombol_kartu_membuka_dialog_check_in_dan_check_out(): void
    {
        $belum = $this->buatAnak('Anak Belum', $this->batita);
        $hadir = $this->buatAnak('Anak Hadir', $this->batita);
        $k = $this->hadir($hadir, '07:45');

        $this->actingAs($this->siti);
        Livewire::test(Presensi::class)
            ->assertDontSee('Form check-in')
            ->call('mulaiCheckIn', $belum->id)
            ->assertSee('Form check-in', false)
            ->assertSee('Check-in: Anak Belum')
            ->call('mulaiCheckOut', $k->id)
            // Membuka dialog lain menutup dialog sebelumnya.
            ->assertSet('anakId', null)
            ->assertSee('Check-out: Anak Hadir')
            ->call('batal')
            ->assertDontSee('Check-out: Anak Hadir');
    }

    public function test_tautan_lama_check_out_dan_kehadiran_dialihkan_ke_presensi(): void
    {
        $this->actingAs($this->siti)->get('/pengasuh/check-out')->assertRedirect('/pengasuh/presensi');
        $this->actingAs($this->siti)->get('/pengasuh/kehadiran')->assertRedirect('/pengasuh/presensi');
    }

    public function test_menu_presensi_hanya_satu_item_tanpa_submenu(): void
    {
        $this->actingAs($this->siti)->get(route('dashboard'))
            ->assertSee(route('pengasuh.presensi'), false)
            ->assertDontSee('Kehadiran Hari Ini');
    }

    public function test_jadwal_saya_menandai_sedang_bertugas_dan_hari_libur(): void
    {
        $this->buatAnak('Anak Satu', $this->batita);

        $this->actingAs($this->siti);
        Livewire::test(JadwalSaya::class)
            ->assertSee('Sedang bertugas')
            ->assertSee('(hari ini)')
            ->assertSee('Libur'); // Sabtu dan Minggu tanpa shift

        Carbon::setTestNow(Carbon::parse('2026-11-18 16:00:00')); // shift sudah selesai
        Livewire::test(JadwalSaya::class)->assertDontSee('Sedang bertugas');
    }

    public function test_garis_waktu_kegiatan_memuat_legenda_dan_aksi(): void
    {
        $kegiatan = JadwalKegiatan::create([
            'kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => 'Sarapan bersama', 'jenis_kegiatan' => 'makan',
            'hari' => null, 'jam_mulai' => '07:30:00', 'jam_selesai' => '08:00:00', 'catatan' => 'Bubur sayur',
        ]);

        $this->actingAs($this->admin);
        Livewire::test(JadwalKegiatanIndex::class)
            ->assertSee('Keterangan warna jenis kegiatan')
            ->assertSee('Sarapan bersama')
            ->assertSee('Bubur sayur')
            ->assertSee('Setiap hari')
            ->assertSeeHtml(route('admin.jadwal-kegiatan.ubah', $kegiatan));
    }

    public function test_kartu_ringkasan_rekap_menghitung_angka_yang_benar(): void
    {
        $a = $this->buatAnak('Anak A', $this->batita);
        $b = $this->buatAnak('Anak B', $this->batita);
        $this->hadir($a, '07:00', '15:00'); // 8 jam
        $this->hadir($b, '08:00');          // masih di tempat

        $this->actingAs($this->admin);
        $uji = Livewire::test(RekapKehadiran::class)
            ->assertSee('Total check-in')
            ->assertSee('Masih di tempat')
            ->assertSee('07:30')   // rata-rata datang (07:00 dan 08:00)
            ->assertSee('8 j 0 m');

        $statistik = $uji->viewData('statistik');
        $this->assertSame(2, $statistik['total']);
        $this->assertSame(2, $statistik['anak']);
        $this->assertSame(1, $statistik['pulang']);
        $this->assertSame(1, $statistik['masih']);
        $this->assertSame(480, $statistik['rata_menit_di']);
    }

    public function test_kartu_ringkasan_tidak_tampil_saat_tidak_ada_data(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(RekapKehadiran::class)
            ->assertDontSee('Total check-in')
            ->assertSee('Tidak ada data kehadiran');
    }
}
