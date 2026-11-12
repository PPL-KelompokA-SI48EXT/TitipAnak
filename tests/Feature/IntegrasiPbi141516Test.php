<?php

namespace Tests\Feature;

use App\Livewire\Admin\JadwalKegiatanBuat;
use App\Livewire\Admin\JadwalShiftBuat;
use App\Livewire\Admin\JadwalShiftIndex;
use App\Livewire\Admin\JadwalShiftUbah;
use App\Livewire\Admin\RekapKehadiran;
use App\Livewire\Pengasuh\CheckOut;
use App\Livewire\Pengasuh\KehadiranHariIni;
use App\Livewire\Pengasuh\Presensi;
use App\Models\IzinPenjemputan;
use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\PenjemputTerdaftar;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

/**
 * Integration test lintas PBI-14 (jadwal shift), PBI-15 (jadwal kegiatan), dan PBI-16 (presensi):
 * satu hari kerja penuh dari penyusunan jadwal oleh admin sampai rekap dan koreksi data.
 */
class IntegrasiPbi141516Test extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $admin;

    private User $siti;

    private User $dewi;

    private User $orangTua;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        // Senin 16 Nov 2026, 08.00 WIB.
        Carbon::setTestNow(Carbon::parse('2026-11-16 08:00:00'));

        $this->admin = User::factory()->create(['nama' => 'Admin'])->assignRole('admin');
        $this->siti = User::factory()->create(['nama' => 'Siti Rahmawati'])->assignRole('pengasuh');
        $this->dewi = User::factory()->create(['nama' => 'Dewi Lestari'])->assignRole('pengasuh');
        $this->orangTua = User::factory()->create(['nama' => 'Ibu Aisyah'])->assignRole('orang_tua');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function buatShift(User $pengasuh, KelompokUsia $kelompok): void
    {
        Livewire::actingAs($this->admin)->test(JadwalShiftBuat::class)
            ->set('pengasuh_id', (string) $pengasuh->id)
            ->set('kelompok_usia_id', (string) $kelompok->id)
            ->set('hari', '1')
            ->set('jam_mulai', '07:00')
            ->set('jam_selesai', '16:00')
            ->call('simpan')
            ->assertHasNoErrors();
    }

    public function test_satu_hari_kerja_dari_penyusunan_jadwal_sampai_rekap_dan_koreksi(): void
    {
        // --- PBI-14: admin menyusun shift. Siti memegang Batita, Dewi memegang Balita. ---
        $this->buatShift($this->siti, $this->batita);
        $this->buatShift($this->dewi, $this->balita);
        $this->assertDatabaseCount('jadwal_shift', 2);

        // --- PBI-15: admin menyusun rutinitas Batita. ---
        Livewire::actingAs($this->admin)->test(JadwalKegiatanBuat::class)
            ->call('pakaiPreset', 'Sarapan')
            ->set('kelompok_usia_id', (string) $this->batita->id)
            ->call('simpan')
            ->assertHasNoErrors();
        $this->assertDatabaseHas('jadwal_kegiatan', ['kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => 'Sarapan']);

        // Anak-anak terdaftar: dua di Batita, satu di Balita.
        $aisyah = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->orangTua);
        $bima = $this->buatAnak('Bima Pratama', $this->batita);
        $citra = $this->buatAnak('Citra Dewi', $this->balita);
        $ibu = PenjemputTerdaftar::create(['anak_id' => $aisyah->id, 'nama_lengkap' => 'Ibu Aisyah', 'hubungan' => 'Ibu', 'no_hp' => '0811']);
        $izinBima = IzinPenjemputan::create([
            'anak_id' => $bima->id, 'diajukan_oleh_id' => $this->orangTua->id, 'nama_penjemput' => 'Om Budi',
            'path_foto_identitas' => 'identitas/budi.jpg', 'alasan' => 'Orang tua dinas luar', 'tanggal_jemput' => today(),
            'status' => 'disetujui',
        ]);

        // --- Siti (pengasuh) melihat jadwal dan kegiatannya: hanya Batita dan anak Batita. ---
        $this->actingAs($this->siti)->get(route('pengasuh.jadwal-saya'))
            ->assertOk()->assertSee('Batita')->assertSee('Aisyah Putri')->assertSee('Bima Pratama')
            ->assertDontSee('Citra Dewi');
        $this->actingAs($this->siti)->get(route('pengasuh.jadwal-kegiatan'))
            ->assertOk()->assertSee('Sarapan')->assertSee('07:30 – 08:00');

        // --- PBI-16: check-in. Siti hanya bisa mencatat anak Batita. ---
        $checkIn = Livewire::actingAs($this->siti)->test(Presensi::class);
        $checkIn->call('mulaiCheckIn', $citra->id)->assertSet('anakId', null);

        foreach ([[$aisyah, '07:45', 'Sehat dan ceria'], [$bima, '08:00', 'Batuk atau pilek']] as [$anak, $jam, $kondisi]) {
            $checkIn->call('mulaiCheckIn', $anak->id)
                ->set('jam_datang', $jam)
                ->call('pakaiKondisi', $kondisi)
                ->call('simpan')
                ->assertHasNoErrors()
                ->assertSee("Check-in {$anak->nama_lengkap} berhasil dicatat.");
        }
        $this->assertDatabaseCount('kehadiran', 2);

        // Check-in ganda ditolak dengan pesan jelas.
        $checkIn->call('mulaiCheckIn', $aisyah->id)->assertSee('sudah check-in hari ini pukul 07:45');
        $this->assertDatabaseCount('kehadiran', 2);

        // Daftar kehadiran: 2 hadir, tidak ada yang belum datang, Citra tidak muncul.
        Livewire::actingAs($this->siti)->test(KehadiranHariIni::class)
            ->assertSee('Hadir · 2')->assertSee('Belum datang · 0')->assertDontSee('Citra Dewi');

        // --- Check-out: Aisyah dijemput ibu terdaftar; Bima oleh Om Budi lewat izin; orang asing ditolak. ---
        Carbon::setTestNow(Carbon::parse('2026-11-16 15:30:00'));
        $kAisyah = Kehadiran::where('anak_id', $aisyah->id)->first();
        $kBima = Kehadiran::where('anak_id', $bima->id)->first();

        $pulang = Livewire::actingAs($this->siti)->test(CheckOut::class);
        $pulang->call('mulaiCheckOut', $kAisyah->id)
            ->set('jam_pulang', '15:20')
            ->set('penjemput', CheckOut::LAIN)
            ->set('nama_penjemput', 'Orang Asing')
            ->call('simpan')
            ->assertSet('tidakCocok', true)
            ->assertSee('Jangan lepas anak ini dulu');
        $this->assertNull($kAisyah->fresh()->check_out_pada);

        $pulang->set('penjemput', (string) $ibu->id)->call('simpan')->assertHasNoErrors();
        $pulang->call('mulaiCheckOut', $kBima->id)
            ->set('jam_pulang', '15:25')
            ->set('penjemput', CheckOut::IZIN.$izinBima->id)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame($ibu->id, $kAisyah->fresh()->penjemput_terdaftar_id);
        $this->assertSame($izinBima->id, $kBima->fresh()->izin_penjemputan_id);
        $this->assertSame('Om Budi', $kBima->fresh()->nama_penjemput);

        // Izin yang sudah dipakai tidak muncul lagi dan semua anak sudah pulang.
        Livewire::actingAs($this->siti)->test(KehadiranHariIni::class)
            ->assertSee('Sudah pulang · 2')->assertSee('Hadir · 0');

        // --- Admin: rekap menampilkan kedua catatan dan ringkasan per anak. ---
        $rekap = Livewire::actingAs($this->admin)->test(RekapKehadiran::class)
            ->set('dari', '2026-11-16')->set('sampai', '2026-11-16')
            ->assertSee('2 catatan kehadiran')->assertSee('Aisyah Putri')->assertSee('Om Budi');
        $rekap->call('setTampilan', 'anak')->assertSee('2 anak · 2 catatan kehadiran');

        // --- Admin mengoreksi: catatan Bima salah input, dihapus dengan alasan. Bima bisa dicatat ulang. ---
        $rekap->call('setTampilan', 'catatan')
            ->call('konfirmasiHapus', $kBima->id)
            ->set('alasan', 'Salah memilih anak saat check-in')
            ->call('hapus')
            ->assertSee('berhasil dihapus');
        $this->assertDatabaseMissing('kehadiran', ['id' => $kBima->id]);
        $this->assertDatabaseHas('log_penghapusan_kehadiran', ['kehadiran_id' => $kBima->id, 'dihapus_oleh_id' => $this->admin->id]);

        Livewire::actingAs($this->siti)->test(Presensi::class)
            ->call('mulaiCheckIn', $bima->id)->assertSet('anakId', $bima->id);
    }

    public function test_admin_mengganti_pengasuh_shift_dan_presensi_mengikuti(): void
    {
        $this->buatShift($this->siti, $this->batita);
        $shift = JadwalShift::first();
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        $this->actingAs($this->siti)->get(route('pengasuh.presensi'))->assertSee('Aisyah Putri');
        $this->actingAs($this->dewi)->get(route('pengasuh.presensi'))->assertDontSee('Aisyah Putri');

        Livewire::actingAs($this->admin)->test(JadwalShiftUbah::class, ['jadwal' => $shift])
            ->set('pengasuh_id', (string) $this->dewi->id)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->actingAs($this->siti)->get(route('pengasuh.presensi'))->assertDontSee('Aisyah Putri');
        $this->actingAs($this->dewi)->get(route('pengasuh.presensi'))->assertSee('Aisyah Putri');
        $this->assertNotNull($anak);
    }

    public function test_admin_menghapus_shift_dan_pengasuh_tidak_lagi_bertanggung_jawab(): void
    {
        $this->buatShift($this->siti, $this->batita);
        $shift = JadwalShift::first();
        $this->buatAnak('Aisyah Putri', $this->batita);

        Livewire::actingAs($this->admin)->test(JadwalShiftIndex::class)
            ->call('konfirmasiHapus', $shift->id)->call('hapus');

        $this->actingAs($this->siti)->get(route('pengasuh.presensi'))
            ->assertSee('Tidak ada anak untuk dicatat')->assertDontSee('Aisyah Putri');
        $this->actingAs($this->siti)->get(route('pengasuh.jadwal-saya'))->assertSee('Tidak ada shift hari ini');
    }

    public function test_setiap_peran_hanya_melihat_menu_dan_halaman_miliknya(): void
    {
        $adminMenu = [route('admin.jadwal-shift.index'), route('admin.jadwal-kegiatan.index'), route('admin.kehadiran.rekap')];
        $pengasuhMenu = [route('pengasuh.jadwal-saya'), route('pengasuh.jadwal-kegiatan'), route('pengasuh.presensi'), route('pengasuh.check-out'), route('pengasuh.kehadiran')];

        $halamanAdmin = $this->actingAs($this->admin)->get(route('dashboard'));
        foreach ($adminMenu as $url) {
            $halamanAdmin->assertSee($url, false);
        }
        foreach ($pengasuhMenu as $url) {
            $halamanAdmin->assertDontSee($url, false);
        }

        $halamanPengasuh = $this->actingAs($this->siti)->get(route('dashboard'));
        foreach ($pengasuhMenu as $url) {
            $halamanPengasuh->assertSee($url, false);
        }
        foreach ($adminMenu as $url) {
            $halamanPengasuh->assertDontSee($url, false);
        }

        foreach (array_merge($adminMenu, $pengasuhMenu) as $url) {
            $this->actingAs($this->orangTua)->get($url)->assertForbidden();
        }
        foreach ($adminMenu as $url) {
            $this->actingAs($this->siti)->get($url)->assertForbidden();
        }
        foreach ($pengasuhMenu as $url) {
            $this->actingAs($this->admin)->get($url)->assertForbidden();
        }
    }
}
