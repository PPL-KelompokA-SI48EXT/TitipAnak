<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\CheckOut;
use App\Models\Anak;
use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\PenjemputTerdaftar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class CheckOutFormTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private KelompokUsia $batita;

    private KelompokUsia $balita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
    }

    private function datang(Anak $anak, ?string $pulang = null, ?string $tanggal = null): Kehadiran
    {
        $tanggal ??= today()->toDateString();

        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal 07:50:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $this->siti->id, 'check_out_pada' => $pulang ? "$tanggal $pulang:00" : null,
        ]);
    }

    public function test_hanya_anak_yang_sudah_check_in_dan_belum_pulang_tampil(): void
    {
        $this->datang($this->buatAnak('Anak Menunggu', $this->batita));
        $this->datang($this->buatAnak('Anak Sudah Pulang', $this->batita), '15:00');
        $this->buatAnak('Anak Belum Datang', $this->batita);
        $this->datang($this->buatAnak('Anak Kelompok Lain', $this->balita));
        $this->datang($this->buatAnak('Anak Kemarin', $this->batita), null, today()->subDay()->toDateString());

        $this->actingAs($this->siti)
            ->get(route('pengasuh.check-out'))
            ->assertOk()
            ->assertSee('Anak Menunggu')
            ->assertSee('datang 07:50')
            ->assertDontSee('Anak Sudah Pulang')
            ->assertDontSee('Anak Belum Datang')
            ->assertDontSee('Anak Kelompok Lain')
            ->assertDontSee('Anak Kemarin');
    }

    public function test_keadaan_kosong_bila_tidak_ada_yang_menunggu(): void
    {
        $this->actingAs($this->siti)
            ->get(route('pengasuh.check-out'))
            ->assertSee('Tidak ada anak yang menunggu dijemput');
    }

    public function test_form_menampilkan_penjemput_terdaftar_aktif_saja(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);
        $k = $this->datang($anak);
        PenjemputTerdaftar::create(['anak_id' => $anak->id, 'nama_lengkap' => 'Ibu Aktif', 'hubungan' => 'Ibu', 'no_hp' => '0811', 'aktif' => true]);
        PenjemputTerdaftar::create(['anak_id' => $anak->id, 'nama_lengkap' => 'Paman Nonaktif', 'hubungan' => 'Paman', 'no_hp' => '0812', 'aktif' => false]);
        $lain = $this->buatAnak('Anak Lain', $this->batita);
        PenjemputTerdaftar::create(['anak_id' => $lain->id, 'nama_lengkap' => 'Orang Asing', 'hubungan' => 'Ayah', 'no_hp' => '0813']);

        Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $k->id)
            ->assertSet('kehadiranId', $k->id)
            ->assertSet('jam_pulang', now()->format('H:i'))
            ->assertSee('Check-out: Aisyah Putri')
            ->assertSee('Ibu Aktif (Ibu)')
            ->assertDontSee('Paman Nonaktif')
            ->assertDontSee('Orang Asing')
            ->assertSee('Penjemput lain');
    }

    public function test_pilihan_penjemput_lain_memunculkan_kolom_nama(): void
    {
        $k = $this->datang($this->buatAnak('Aisyah Putri', $this->batita));

        Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $k->id)
            ->assertDontSee('Nama penjemput')
            ->set('penjemput', CheckOut::LAIN)
            ->assertSee('Nama penjemput')
            ->assertSee('belum mendaftarkan penjemput');
    }

    public function test_tidak_bisa_membuka_form_untuk_anak_yang_sudah_pulang_atau_kelompok_lain(): void
    {
        $pulang = $this->datang($this->buatAnak('Anak Pulang', $this->batita), '15:00');
        $asing = $this->datang($this->buatAnak('Anak Asing', $this->balita));

        Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $pulang->id)
            ->assertSet('kehadiranId', null)
            ->call('mulaiCheckOut', $asing->id)
            ->assertSet('kehadiranId', null);
    }

    public function test_batal_menutup_form(): void
    {
        $k = $this->datang($this->buatAnak('Aisyah Putri', $this->batita));

        Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $k->id)
            ->set('penjemput', CheckOut::LAIN)
            ->call('batal')
            ->assertSet('kehadiranId', null)
            ->assertSet('penjemput', '')
            ->assertDontSee('Simpan check-out');
    }

    public function test_kolom_wajib_diperiksa(): void
    {
        $k = $this->datang($this->buatAnak('Aisyah Putri', $this->batita));

        Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $k->id)
            ->set('jam_pulang', '')
            ->call('simpan')
            ->assertHasErrors(['jam_pulang', 'penjemput']);
    }

    public function test_admin_dan_tamu_tidak_bisa_membuka_halaman(): void
    {
        $this->get(route('pengasuh.check-out'))->assertRedirect();

        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->get(route('pengasuh.check-out'))->assertForbidden();
    }

    public function test_menu_presensi_memuat_tautan_check_out(): void
    {
        $this->actingAs($this->siti)
            ->get(route('dashboard'))
            ->assertSee(route('pengasuh.check-out'), false);
    }
}
