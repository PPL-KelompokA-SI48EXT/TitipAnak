<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\CheckOut;
use App\Models\Anak;
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

class CheckOutSimpanTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private KelompokUsia $batita;

    private Anak $anak;

    private Kehadiran $kehadiran;

    private PenjemputTerdaftar $ibu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow(today()->setTime(16, 0));

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '17:00:00',
        ]);

        $this->anak = $this->buatAnak('Aisyah Putri', $this->batita);
        $this->ibu = PenjemputTerdaftar::create(['anak_id' => $this->anak->id, 'nama_lengkap' => 'Ibu Aisyah', 'hubungan' => 'Ibu', 'no_hp' => '0811']);
        $tanggal = today()->toDateString();
        $this->kehadiran = Kehadiran::create([
            'anak_id' => $this->anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal 07:50:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $this->siti->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function form(string $jam = '15:45', string $penjemput = '', string $nama = '')
    {
        return Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $this->kehadiran->id)
            ->set('jam_pulang', $jam)
            ->set('penjemput', $penjemput)
            ->set('nama_penjemput', $nama);
    }

    public function test_check_out_dengan_penjemput_terdaftar_yang_dipilih(): void
    {
        $this->form('15:45', (string) $this->ibu->id)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSet('kehadiranId', null)
            ->assertSee('Check-out Aisyah Putri berhasil dicatat. Dijemput oleh Ibu Aisyah.');

        $k = $this->kehadiran->fresh();
        $this->assertSame('15:45', $k->check_out_pada->format('H:i'));
        $this->assertSame($this->siti->id, $k->check_out_oleh_id);
        $this->assertSame($this->ibu->id, $k->penjemput_terdaftar_id);
        $this->assertSame('Ibu Aisyah', $k->nama_penjemput);
        $this->assertNull($k->izin_penjemputan_id);
    }

    public function test_penjemput_lain_dengan_nama_yang_sama_dikenali_sebagai_terdaftar(): void
    {
        $this->form('15:45', CheckOut::LAIN, '   ibu   AISYAH ')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame($this->ibu->id, $this->kehadiran->fresh()->penjemput_terdaftar_id);
        $this->assertSame('Ibu Aisyah', $this->kehadiran->fresh()->nama_penjemput);
    }

    public function test_nama_yang_tidak_cocok_ditolak_dan_anak_tetap_di_tempat(): void
    {
        $this->form('15:45', CheckOut::LAIN, 'Orang Asing')
            ->call('simpan')
            ->assertHasErrors(['nama_penjemput'])
            ->assertSet('kehadiranId', $this->kehadiran->id);

        $this->assertNull($this->kehadiran->fresh()->check_out_pada);
    }

    public function test_penjemput_nonaktif_atau_milik_anak_lain_tidak_cocok(): void
    {
        $this->ibu->update(['aktif' => false]);
        $lain = $this->buatAnak('Anak Lain', $this->batita);
        $milikLain = PenjemputTerdaftar::create(['anak_id' => $lain->id, 'nama_lengkap' => 'Ayah Lain', 'hubungan' => 'Ayah', 'no_hp' => '0822']);

        $this->form('15:45', (string) $this->ibu->id)->call('simpan')->assertHasErrors(['nama_penjemput']);
        $this->form('15:45', (string) $milikLain->id)->call('simpan')->assertHasErrors(['nama_penjemput']);
        $this->form('15:45', CheckOut::LAIN, 'Ayah Lain')->call('simpan')->assertHasErrors(['nama_penjemput']);
        $this->form('15:45', CheckOut::LAIN, 'Ibu Aisyah')->call('simpan')->assertHasErrors(['nama_penjemput']);

        $this->assertNull($this->kehadiran->fresh()->check_out_pada);
    }

    public function test_pilihan_penjemput_yang_bukan_angka_atau_id_tidak_ada_ditolak(): void
    {
        $this->form('15:45', 'asal')->call('simpan')->assertHasErrors(['nama_penjemput']);
        $this->form('15:45', '99999')->call('simpan')->assertHasErrors(['nama_penjemput']);
    }

    public function test_nama_wajib_diisi_untuk_penjemput_lain(): void
    {
        $this->form('15:45', CheckOut::LAIN, '')
            ->call('simpan')
            ->assertHasErrors(['nama_penjemput']);
    }

    public function test_jam_pulang_tidak_boleh_lewat_sekarang_atau_sebelum_jam_datang(): void
    {
        $this->form('16:01', (string) $this->ibu->id)->call('simpan')->assertHasErrors(['jam_pulang']);
        $this->form('07:49', (string) $this->ibu->id)->call('simpan')->assertHasErrors(['jam_pulang']);
        $this->form('07:50', (string) $this->ibu->id)->call('simpan')->assertHasNoErrors();
    }

    public function test_format_jam_salah_ditolak(): void
    {
        $this->form('sore', (string) $this->ibu->id)->call('simpan')->assertHasErrors(['jam_pulang']);
    }

    public function test_anak_yang_sudah_pulang_tidak_bisa_dicatat_dua_kali(): void
    {
        $komponen = $this->form('15:45', (string) $this->ibu->id);
        $this->kehadiran->update(['check_out_pada' => today()->setTime(15, 0), 'check_out_oleh_id' => $this->siti->id]);

        $komponen->call('simpan')->assertSee('sudah dicatat pulang');

        $this->assertSame('15:00', $this->kehadiran->fresh()->check_out_pada->format('H:i'));
    }

    public function test_pengguna_tanpa_izin_ditolak(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        Livewire::actingAs($admin)->test(CheckOut::class)->assertForbidden();
    }
}
