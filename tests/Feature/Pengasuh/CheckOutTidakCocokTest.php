<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\CheckOut;
use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\PenjemputTerdaftar;
use App\Models\ProfilOrangTua;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class CheckOutTidakCocokTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private Kehadiran $kehadiran;

    private PenjemputTerdaftar $ibu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow(today()->setTime(16, 0));

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '17:00:00',
        ]);

        $orangTua = User::factory()->create(['nama' => 'Ibu Aisyah'])->assignRole('orang_tua');
        ProfilOrangTua::create(['pengguna_id' => $orangTua->id, 'nama_lengkap' => 'Ibu Aisyah Rahma', 'no_hp' => '081234567890']);
        $anak = $this->buatAnak('Aisyah Putri', $batita, 'aktif', $orangTua);
        $this->ibu = PenjemputTerdaftar::create(['anak_id' => $anak->id, 'nama_lengkap' => 'Ibu Aisyah', 'hubungan' => 'Ibu', 'no_hp' => '0811']);
        PenjemputTerdaftar::create(['anak_id' => $anak->id, 'nama_lengkap' => 'Nenek Siti', 'hubungan' => 'Nenek', 'no_hp' => '0812']);
        $tanggal = today()->toDateString();
        $this->kehadiran = Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal 07:50:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $this->siti->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function form(string $penjemput, string $nama = '')
    {
        return Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $this->kehadiran->id)
            ->set('jam_pulang', '15:45')
            ->set('penjemput', $penjemput)
            ->set('nama_penjemput', $nama);
    }

    public function test_penjemput_tidak_cocok_menampilkan_panduan_lengkap(): void
    {
        $this->form(CheckOut::LAIN, 'Orang Asing')
            ->call('simpan')
            ->assertSet('tidakCocok', true)
            ->assertSee('Penjemput tidak cocok. Jangan lepas anak ini dulu.')
            ->assertSee('Ibu Aisyah, Nenek Siti')
            ->assertSee('Ibu Aisyah Rahma')
            ->assertSee('081234567890')
            ->assertSee('Penjemput tidak cocok dengan Penjemput Terdaftar atau izin penjemputan anak ini.');

        $this->assertNull($this->kehadiran->fresh()->check_out_pada);
    }

    public function test_kontak_orang_tua_tidak_ada_diganti_arahan_ke_admin(): void
    {
        ProfilOrangTua::query()->delete();

        $this->form(CheckOut::LAIN, 'Orang Asing')
            ->call('simpan')
            ->assertSee('kontak orang tua belum tersedia, hubungi admin');
    }

    public function test_pilihan_id_yang_salah_juga_memunculkan_panduan(): void
    {
        $this->form('99999')
            ->call('simpan')
            ->assertSet('tidakCocok', true)
            ->assertHasErrors(['penjemput']);
    }

    public function test_panduan_hilang_saat_pengasuh_mengubah_penjemput_dan_mencoba_lagi(): void
    {
        $this->form(CheckOut::LAIN, 'Orang Asing')
            ->call('simpan')
            ->assertSet('tidakCocok', true)
            ->set('nama_penjemput', 'Ibu Aisyah')
            ->assertSet('tidakCocok', false)
            ->assertDontSee('Jangan lepas anak ini dulu')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame($this->ibu->id, $this->kehadiran->fresh()->penjemput_terdaftar_id);
    }

    public function test_panduan_tidak_muncul_saat_check_out_berhasil_atau_form_dibatalkan(): void
    {
        $this->form((string) $this->ibu->id)
            ->call('simpan')
            ->assertSet('tidakCocok', false)
            ->assertDontSee('Jangan lepas anak ini dulu');

        $this->form(CheckOut::LAIN, 'Orang Asing')
            ->call('simpan')
            ->call('batal')
            ->assertSet('tidakCocok', false);
    }

    public function test_tanpa_penjemput_terdaftar_panduan_menyebutkan_belum_ada(): void
    {
        PenjemputTerdaftar::query()->delete();

        $this->form(CheckOut::LAIN, 'Siapa Saja')
            ->call('simpan')
            ->assertSee('belum ada yang terdaftar');
    }
}
