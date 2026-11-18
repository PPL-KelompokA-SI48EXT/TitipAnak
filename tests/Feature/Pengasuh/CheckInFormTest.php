<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\Presensi;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class CheckInFormTest extends TestCase
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

        $this->siti = User::factory()->create(['nama' => 'Siti'])->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->balita = KelompokUsia::create(['nama' => 'Balita', 'usia_min_bulan' => 36, 'usia_maks_bulan' => 47, 'kapasitas' => 15]);

        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);
    }

    public function test_halaman_menampilkan_anak_aktif_di_kelompok_yang_dipegang(): void
    {
        $this->buatAnak('Aisyah Putri', $this->batita);
        $this->buatAnak('Citra Dewi', $this->balita);
        $this->buatAnak('Dimas Nonaktif', $this->batita, 'nonaktif');

        $this->actingAs($this->siti)
            ->get(route('pengasuh.presensi'))
            ->assertOk()
            ->assertSee('Aisyah Putri')
            ->assertDontSee('Citra Dewi')
            ->assertDontSee('Dimas Nonaktif');
    }

    public function test_tanpa_shift_hari_ini_menampilkan_keadaan_kosong(): void
    {
        JadwalShift::query()->delete();
        $this->buatAnak('Aisyah Putri', $this->batita);

        $this->actingAs($this->siti)
            ->get(route('pengasuh.presensi'))
            ->assertSee('Tidak ada anak untuk hari ini')
            ->assertDontSee('Aisyah Putri');
    }

    public function test_mulai_check_in_membuka_form_dengan_jam_sekarang(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        Livewire::actingAs($this->siti)
            ->test(Presensi::class)
            ->assertDontSee('Simpan check-in')
            ->call('mulaiCheckIn', $anak->id)
            ->assertSet('anakId', $anak->id)
            ->assertSet('jam_datang', now()->format('H:i'))
            ->assertSee('Check-in: Aisyah Putri')
            ->assertSee('Simpan check-in');
    }

    public function test_anak_di_luar_kelompok_tidak_bisa_dibuka_formnya(): void
    {
        $asing = $this->buatAnak('Citra Dewi', $this->balita);

        Livewire::actingAs($this->siti)
            ->test(Presensi::class)
            ->call('mulaiCheckIn', $asing->id)
            ->assertSet('anakId', null);
    }

    public function test_pilihan_cepat_mengisi_kondisi_dan_batal_menutup_form(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        Livewire::actingAs($this->siti)
            ->test(Presensi::class)
            ->call('mulaiCheckIn', $anak->id)
            ->call('pakaiKondisi', 'Kurang tidur')
            ->assertSet('kondisi', 'Kurang tidur')
            ->call('pakaiKondisi', 'asal')
            ->assertSet('kondisi', 'Kurang tidur')
            ->call('batal')
            ->assertSet('anakId', null)
            ->assertSet('kondisi', '');
    }

    public function test_kolom_wajib_diperiksa(): void
    {
        $anak = $this->buatAnak('Aisyah Putri', $this->batita);

        Livewire::actingAs($this->siti)
            ->test(Presensi::class)
            ->call('mulaiCheckIn', $anak->id)
            ->set('jam_datang', '')
            ->call('simpan')
            ->assertHasErrors(['jam_datang', 'kondisi']);
    }

    public function test_admin_dan_tamu_tidak_bisa_membuka_halaman(): void
    {
        $this->get(route('pengasuh.presensi'))->assertRedirect();

        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->get(route('pengasuh.presensi'))->assertForbidden();
    }

    public function test_menu_presensi_tampil_untuk_pengasuh(): void
    {
        $this->actingAs($this->siti)
            ->get(route('dashboard'))
            ->assertSee(route('pengasuh.presensi'), false);
    }
}
