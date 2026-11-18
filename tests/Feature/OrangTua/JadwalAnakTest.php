<?php

namespace Tests\Feature\OrangTua;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

/** PBI-15f: orang tua melihat jadwal kegiatan harian dan pengasuh yang menjaga anaknya. */
class JadwalAnakTest extends TestCase
{
    use MembuatAnak;
    use RefreshDatabase;

    private User $ibu;

    private User $siti;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // Pemilih akun dev di navbar menampilkan semua nama akun; matikan agar pemeriksaan isi halaman akurat.
        config(['titipanak.dev_switcher' => false]);
        $this->seed(RolePermissionSeeder::class);
        // Jam dibekukan agar penanda "sedang berlangsung" tidak bergantung pada kapan tes dijalankan.
        Carbon::setTestNow(today()->setTime(10, 30));

        $this->ibu = User::factory()->create(['nama' => 'Ibu Aisyah'])->assignRole('orang_tua');
        $this->siti = User::factory()->create(['nama' => 'Siti Pengasuh'])->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function shift(KelompokUsia $k, User $pengasuh, string $mulai = '07:00:00', string $selesai = '15:00:00', ?int $hari = null): void
    {
        JadwalShift::create([
            'pengasuh_id' => $pengasuh->id, 'kelompok_usia_id' => $k->id, 'hari' => $hari ?? today()->dayOfWeekIso,
            'jam_mulai' => $mulai, 'jam_selesai' => $selesai,
        ]);
    }

    private function kegiatan(KelompokUsia $k, string $nama, string $mulai, string $selesai, ?string $catatan = null, ?int $hari = null): void
    {
        JadwalKegiatan::create([
            'kelompok_usia_id' => $k->id, 'nama_kegiatan' => $nama, 'jenis_kegiatan' => 'makan', 'hari' => $hari,
            'jam_mulai' => $mulai.':00', 'jam_selesai' => $selesai.':00', 'catatan' => $catatan,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('orang-tua.jadwal-anak'))->assertRedirect(route('login'));
    }

    public function test_pengasuh_dan_admin_tidak_bisa_membuka_halaman_orang_tua(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($this->siti)->get(route('orang-tua.jadwal-anak'))->assertForbidden();
        $this->actingAs($admin)->get(route('orang-tua.jadwal-anak'))->assertForbidden();
    }

    public function test_orang_tua_melihat_pengasuh_dan_kegiatan_kelompok_anaknya(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->shift($this->batita, $this->siti, '07:00:00', '15:00:00');
        $this->kegiatan($this->batita, 'Sarapan Batita', '07:30', '08:00', 'Tanpa kacang');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertOk()
            ->assertSee('Aisyah Putri')
            ->assertSee('Kelompok Batita')
            ->assertSee('Siti Pengasuh')
            ->assertSee('07:00 – 15:00')
            ->assertSee('Sarapan Batita')
            ->assertSee('07:30 – 08:00')
            ->assertSee('Tanpa kacang');
    }

    public function test_kegiatan_diurutkan_menurut_jam(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->kegiatan($this->batita, 'Tidur siang', '12:30', '14:00');
        $this->kegiatan($this->batita, 'Sarapan', '07:30', '08:00');
        $this->kegiatan($this->batita, 'Bermain', '09:00', '10:00');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertSeeInOrder(['Sarapan', 'Bermain', 'Tidur siang']);
    }

    public function test_tidak_menampilkan_anak_atau_jadwal_milik_orang_lain(): void
    {
        $ibuLain = User::factory()->create(['nama' => 'Ibu Lain'])->assignRole('orang_tua');
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->buatAnak('Anak Orang Lain', $this->balita, 'aktif', $ibuLain);

        $pengasuhLain = User::factory()->create(['nama' => 'Pengasuh Balita'])->assignRole('pengasuh');
        $this->shift($this->batita, $this->siti);
        $this->shift($this->balita, $pengasuhLain);
        $this->kegiatan($this->batita, 'Kegiatan Batita', '08:00', '09:00');
        $this->kegiatan($this->balita, 'Kegiatan Balita', '08:00', '09:00');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertOk()
            ->assertSee('Kegiatan Batita')
            ->assertSee('Siti Pengasuh')
            ->assertDontSee('Anak Orang Lain')
            ->assertDontSee('Kegiatan Balita')
            ->assertDontSee('Pengasuh Balita');
    }

    public function test_anak_nonaktif_tidak_ditampilkan(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->buatAnak('Dimas Nonaktif', $this->batita, 'nonaktif', $this->ibu);

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertSee('Aisyah Putri')
            ->assertDontSee('Dimas Nonaktif');
    }

    public function test_dua_anak_di_kelompok_berbeda_masing_masing_punya_jadwal(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->buatAnak('Bagas Kecil', $this->balita, 'aktif', $this->ibu);
        $pengasuhLain = User::factory()->create(['nama' => 'Pengasuh Balita'])->assignRole('pengasuh');
        $this->shift($this->batita, $this->siti);
        $this->shift($this->balita, $pengasuhLain);
        $this->kegiatan($this->batita, 'Kegiatan Batita', '08:00', '09:00');
        $this->kegiatan($this->balita, 'Kegiatan Balita', '09:00', '10:00');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertSeeInOrder(['Aisyah Putri', 'Siti Pengasuh', 'Kegiatan Batita', 'Bagas Kecil', 'Pengasuh Balita', 'Kegiatan Balita']);
    }

    public function test_hanya_kegiatan_dan_shift_yang_berlaku_hari_ini(): void
    {
        $besok = today()->addDay()->dayOfWeekIso;
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->shift($this->batita, $this->siti, hari: $besok);
        $this->kegiatan($this->batita, 'Kegiatan Besok', '08:00', '09:00', hari: $besok);
        $this->kegiatan($this->batita, 'Setiap Hari', '10:00', '11:00');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertSee('Setiap Hari')
            ->assertDontSee('Kegiatan Besok')
            ->assertDontSee('Siti Pengasuh')
            ->assertSee('Belum ada pengasuh yang dijadwalkan');
    }

    public function test_kegiatan_yang_sedang_berlangsung_ditandai(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->kegiatan($this->batita, 'Sedang jalan', '10:00', '11:00');
        $this->kegiatan($this->batita, 'Nanti sore', '15:00', '16:00');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertSee('Sedang berlangsung', false);
    }

    public function test_keadaan_kosong_saat_belum_ada_shift_dan_kegiatan(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertOk()
            ->assertSee('Belum ada pengasuh yang dijadwalkan')
            ->assertSee('Belum ada jadwal kegiatan');
    }

    public function test_orang_tua_tanpa_anak_aktif_melihat_pesan_kosong(): void
    {
        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertOk()
            ->assertSee('Belum ada anak yang aktif');
    }

    public function test_anak_tanpa_kelompok_usia_tidak_menyebabkan_galat(): void
    {
        $this->buatAnak('Aisyah Putri', null, 'aktif', $this->ibu);

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertOk()
            ->assertSee('Aisyah Putri');
    }

    public function test_halaman_hanya_baca_tanpa_tombol_ubah_atau_hapus(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->kegiatan($this->batita, 'Sarapan', '07:30', '08:00');

        $this->actingAs($this->ibu)
            ->get(route('orang-tua.jadwal-anak'))
            ->assertDontSee('Hapus')
            ->assertDontSee('Ubah');
    }

    public function test_menu_jadwal_anak_muncul_untuk_orang_tua_saja(): void
    {
        $this->actingAs($this->ibu)->get(route('dashboard'))->assertSee('Jadwal Anak');
        $this->actingAs($this->siti)->get(route('dashboard'))->assertDontSee('Jadwal Anak');
    }
}
