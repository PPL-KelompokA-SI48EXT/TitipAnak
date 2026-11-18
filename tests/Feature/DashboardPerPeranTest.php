<?php

namespace Tests\Feature;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\Tagihan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

/** PBI-20: dashboard per peran (Admin, Pengasuh, Orang Tua). */
class DashboardPerPeranTest extends TestCase
{
    use MembuatAnak;
    use RefreshDatabase;

    private KelompokUsia $batita;

    private User $siti;

    private User $ibu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['titipanak.dev_switcher' => false]);
        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow(today()->setTime(10, 30));

        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->siti = User::factory()->create(['nama' => 'Siti Pengasuh'])->assignRole('pengasuh');
        $this->ibu = User::factory()->create(['nama' => 'Ibu Aisyah'])->assignRole('orang_tua');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function datang($anak, string $jam = '07:15', ?string $pulang = null, ?string $penjemput = null): Kehadiran
    {
        $tgl = today()->toDateString();

        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tgl, 'check_in_pada' => "$tgl $jam:00", 'kondisi_check_in' => 'Sehat dan ceria',
            'check_in_oleh_id' => $this->siti->id, 'check_out_pada' => $pulang ? "$tgl $pulang:00" : null,
            'check_out_oleh_id' => $pulang ? $this->siti->id : null, 'nama_penjemput' => $penjemput,
        ]);
    }

    private function tagihan($anak, string $status = 'belum_lunas', float $jumlah = 1500000, ?string $jatuhTempo = null): void
    {
        Tagihan::create([
            'pendaftaran_id' => $anak->pendaftaran_id, 'nomor_tagihan' => 'INV-'.uniqid(), 'jumlah' => $jumlah, 'status' => $status,
            'diterbitkan_pada' => now(), 'jatuh_tempo' => $jatuhTempo ?? today()->addDays(7)->toDateString(),
        ]);
    }

    // ---------- Admin ----------

    public function test_dashboard_admin_menampilkan_angka_ringkasan(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $a = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $b = $this->buatAnak('Bagas Pratama', $this->batita, 'aktif');
        $this->buatAnak('Anak Nonaktif', $this->batita, 'nonaktif');
        $this->datang($a, '07:15');
        $this->datang($b, '07:30', '09:50', 'Budi');
        $this->tagihan($a, 'belum_lunas', 1500000);
        $this->tagihan($b, 'lunas', 999000);
        DB::table('pendaftaran')->where('id', $a->pendaftaran_id)->update(['status' => 'menunggu_verifikasi']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Anak aktif', '2'])
            ->assertSee('Hadir hari ini')
            ->assertSee('dari 2 anak aktif')
            ->assertSee('Menunggu verifikasi')
            ->assertSee('Tagihan belum lunas')
            ->assertSee('Rp 1.500.000')
            ->assertDontSee('Rp 999.000')
            ->assertSee(route('admin.pendaftaran.index'), false)
            ->assertSee(route('admin.tagihan.index'), false)
            ->assertSee(route('admin.kehadiran.rekap'), false);
    }

    public function test_dashboard_admin_rincian_per_kelompok_dan_perlu_tindakan(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $a = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif');
        $this->buatAnak('Bagas Pratama', $this->batita, 'aktif');
        $this->datang($a);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee('Batita')
            ->assertSee('1 hadir · 0 dijemput · 1 belum datang · 2 anak')
            ->assertSeeHtml('<b class="text-base">1</b> anak masih di daycare (belum dijemput)')
            ->assertSee('Kehadiran 7 hari terakhir');
    }

    public function test_dashboard_admin_kosong_menampilkan_pesan_beres(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Semua beres')
            ->assertSee('Belum ada anak aktif di kelompok usia mana pun.');
    }

    // ---------- Pengasuh ----------

    public function test_dashboard_pengasuh_menampilkan_shift_anak_dan_status(): void
    {
        $hadir = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif');
        $belum = $this->buatAnak('Bagas Pratama', $this->batita, 'aktif');
        $pulang = $this->buatAnak('Citra Lestari', $this->batita, 'aktif');
        $this->datang($hadir, '07:15');
        $this->datang($pulang, '07:20', '09:50', 'Budi');
        JadwalShift::create(['pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => today()->dayOfWeekIso, 'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00']);
        JadwalKegiatan::create(['kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => 'Bermain balok', 'jenis_kegiatan' => 'bermain', 'hari' => null, 'jam_mulai' => '10:00:00', 'jam_selesai' => '11:00:00']);

        $this->actingAs($this->siti)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('07:00–15:00')
            ->assertSee('Aisyah Putri')
            ->assertSee('Bagas Pratama')
            ->assertSee('Citra Lestari')
            ->assertSee('Belum datang')
            ->assertSee('Sudah dijemput')
            ->assertSee('Datang 07:15')
            ->assertSee('Pulang 09:50')
            ->assertSee('Bermain balok')
            ->assertSee(route('pengasuh.presensi'), false);
        $this->assertNotNull($belum);
    }

    public function test_dashboard_pengasuh_tanpa_shift_hanya_pesan_libur_tanpa_anak_orang_lain(): void
    {
        $this->buatAnak('Anak Kelompok Lain', $this->batita, 'aktif');

        $this->actingAs($this->siti)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Libur')
            ->assertSee('Anda tidak bertugas hari ini.')
            ->assertSee('Belum ada anak yang menjadi tanggung jawab Anda hari ini.')
            ->assertDontSee('Anak Kelompok Lain');
    }

    // ---------- Orang tua ----------

    public function test_dashboard_orang_tua_menampilkan_kabar_tagihan_dan_pendaftaran(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->ibu);
        $this->datang($anak, '07:15');
        $this->tagihan($anak, 'belum_lunas', 2000000, today()->subDays(2)->toDateString());

        $this->actingAs($this->ibu)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kabar anak hari ini')
            ->assertSee('Aisyah Putri')
            ->assertSee('Sedang di daycare')
            ->assertSee('Tiba 07:15')
            ->assertSee('Rp 2.000.000')
            ->assertSee('1 tagihan sudah lewat jatuh tempo')
            ->assertSee('1 disetujui')
            ->assertSee('Segera hadir')
            ->assertSee(route('orang-tua.kabar-hari-ini'), false)
            ->assertSee(route('orang-tua.tagihan.index'), false);
    }

    public function test_dashboard_orang_tua_tidak_memuat_data_orang_lain(): void
    {
        $ibuLain = User::factory()->create()->assignRole('orang_tua');
        $anakLain = $this->buatAnak('Anak Orang Lain', $this->batita, 'aktif', $ibuLain);
        $this->datang($anakLain, '08:45', '09:40', 'Penjemput Rahasia');
        $this->tagihan($anakLain, 'belum_lunas', 7777000);

        $this->actingAs($this->ibu)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Belum ada anak yang aktif')
            ->assertDontSee('Anak Orang Lain')
            ->assertDontSee('Rp 7.777.000')
            ->assertDontSee('Penjemput Rahasia');
    }

    public function test_dashboard_orang_tua_baru_tanpa_apa_pun_menampilkan_keadaan_kosong(): void
    {
        $this->actingAs($this->ibu)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('belum ada pendaftaran')
            ->assertSee('Tidak ada yang perlu Anda urus saat ini.');
    }

    public function test_akun_tanpa_peran_melihat_pesan_bukan_error(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Akun Anda belum punya peran');
    }
}
