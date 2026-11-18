<?php

namespace Tests\Feature\OrangTua;

use App\Models\JadwalKegiatan;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

/** PBI-16f: orang tua melihat kabar anaknya hari ini (tiba, kondisi, kegiatan, dijemput). */
class KabarHariIniTest extends TestCase
{
    use MembuatAnak;
    use RefreshDatabase;

    private User $ibu;

    private User $siti;

    private KelompokUsia $batita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['titipanak.dev_switcher' => false]);
        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow(today()->setTime(10, 30));

        $this->ibu = User::factory()->create(['nama' => 'Ibu Aisyah'])->assignRole('orang_tua');
        $this->siti = User::factory()->create(['nama' => 'Siti Pengasuh'])->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function kegiatan(string $nama, string $mulai, string $selesai, ?string $catatan = null): void
    {
        JadwalKegiatan::create([
            'kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => 'bermain', 'hari' => null,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00', 'catatan' => $catatan,
        ]);
    }

    private function datang($anak, string $jam = '07:15', string $kondisi = 'Sehat dan ceria', ?string $pulang = null, ?string $penjemput = null): Kehadiran
    {
        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => today()->toDateString(),
            'check_in_pada' => today()->toDateString()." $jam:00", 'kondisi_check_in' => $kondisi, 'check_in_oleh_id' => $this->siti->id,
            'check_out_pada' => $pulang ? today()->toDateString()." $pulang:00" : null,
            'check_out_oleh_id' => $pulang ? $this->siti->id : null, 'nama_penjemput' => $penjemput,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('orang-tua.kabar-hari-ini'))->assertRedirect(route('login'));
    }

    public function test_pengasuh_dan_admin_tidak_bisa_membuka_halaman_orang_tua(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($this->siti)->get(route('orang-tua.kabar-hari-ini'))->assertForbidden();
        $this->actingAs($admin)->get(route('orang-tua.kabar-hari-ini'))->assertForbidden();
    }

    public function test_anak_belum_datang(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.kabar-hari-ini'))
            ->assertOk()
            ->assertSee('Kabar Aisyah Putri hari ini')
            ->assertSee('Belum datang')
            ->assertSee('Belum tiba')
            ->assertDontSee('Belum dijemput');
    }

    public function test_anak_hadir_menampilkan_jam_kondisi_dan_belum_dijemput(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->datang($anak, '07:15', 'Rewel');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.kabar-hari-ini'))
            ->assertOk()
            ->assertSee('Sedang di daycare')
            ->assertSee('Tiba di daycare')
            ->assertSee('07:15')
            ->assertSee('Rewel')
            ->assertSee('Belum dijemput')
            ->assertDontSee('Belum tiba');
    }

    public function test_anak_sudah_dijemput_menampilkan_jam_dan_nama_penjemput(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->datang($anak, '07:15', 'Sehat dan ceria', '09:50', 'Budi Santoso');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.kabar-hari-ini'))
            ->assertOk()
            ->assertSee('Sudah dijemput')
            ->assertSee('09:50')
            ->assertSee('Oleh Budi Santoso')
            ->assertDontSee('Belum dijemput');
    }

    public function test_menampilkan_kegiatan_berlangsung_dan_berikutnya_saat_anak_hadir(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->datang($anak);
        $this->kegiatan('Sarapan', '07:30', '08:00');
        $this->kegiatan('Bermain balok', '10:00', '11:00', 'Bersama teman');
        $this->kegiatan('Tidur siang', '12:30', '14:00');

        // Jam beku 10:30: Bermain balok berlangsung, Tidur siang berikutnya, Sarapan sudah lewat.
        $this->actingAs($this->ibu)
            ->get(route('orang-tua.kabar-hari-ini'))
            ->assertSeeInOrder(['Bermain balok', 'Sedang berlangsung', 'Tidur siang', 'Berikutnya'])
            ->assertSee('Bersama teman')
            ->assertDontSee('Sarapan');
    }

    public function test_kegiatan_tidak_ditampilkan_saat_anak_belum_datang_atau_sudah_pulang(): void
    {
        $belum = $this->buatAnak('Anak Belum', $this->batita, 'aktif', $this->ibu);
        $pulang = $this->buatAnak('Anak Pulang', $this->batita, 'aktif', $this->ibu);
        $this->datang($pulang, '07:15', 'Sehat dan ceria', '09:50', 'Budi');
        $this->kegiatan('Bermain balok', '10:00', '11:00');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.kabar-hari-ini'))
            ->assertOk()
            ->assertSee('Anak Belum')
            ->assertSee('Anak Pulang')
            ->assertDontSee('Bermain balok');
        $this->assertNotNull($belum);
    }

    public function test_pesan_jelas_saat_tidak_ada_jadwal_atau_semua_kegiatan_selesai(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->datang($anak);

        $this->actingAs($this->ibu)->get(route('orang-tua.kabar-hari-ini'))
            ->assertSee('Belum ada jadwal kegiatan untuk kelompok ini hari ini.');

        $this->kegiatan('Sarapan', '07:30', '08:00');

        $this->actingAs($this->ibu)->get(route('orang-tua.kabar-hari-ini'))
            ->assertSee('Semua kegiatan hari ini sudah selesai.');
    }

    public function test_tidak_menampilkan_anak_atau_kehadiran_milik_orang_lain(): void
    {
        $ibuLain = User::factory()->create(['nama' => 'Ibu Lain'])->assignRole('orang_tua');
        $anakSaya = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $anakLain = $this->buatAnak('Anak Orang Lain', $this->batita, 'aktif', $ibuLain);
        $this->datang($anakSaya, '07:15');
        $this->datang($anakLain, '08:45', 'Demam ringan', '09:40', 'Penjemput Rahasia');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.kabar-hari-ini'))
            ->assertOk()
            ->assertSee('Aisyah Putri')
            ->assertDontSee('Anak Orang Lain')
            ->assertDontSee('Penjemput Rahasia')
            ->assertDontSee('Demam ringan')
            ->assertDontSee('08:45');
    }

    public function test_kehadiran_hari_lain_diabaikan_dan_anak_nonaktif_tidak_muncul(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->buatAnak('Anak Nonaktif', $this->batita, 'nonaktif', $this->ibu);
        Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => today()->subDay()->toDateString(),
            'check_in_pada' => today()->subDay()->toDateString().' 06:45:00', 'kondisi_check_in' => 'Rewel', 'check_in_oleh_id' => $this->siti->id,
        ]);

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.kabar-hari-ini'))
            ->assertSee('Belum tiba')
            ->assertDontSee('06:45')
            ->assertDontSee('Anak Nonaktif');
    }

    public function test_keadaan_kosong_tanpa_anak_aktif(): void
    {
        $this->actingAs($this->ibu)
            ->get(route('orang-tua.kabar-hari-ini'))
            ->assertOk()
            ->assertSee('Belum ada anak yang aktif');
    }

    public function test_menu_orang_tua_memuat_kabar_hari_ini(): void
    {
        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertSee('Kabar Hari Ini')
            ->assertSee(route('orang-tua.kabar-hari-ini'), false);
    }
}
