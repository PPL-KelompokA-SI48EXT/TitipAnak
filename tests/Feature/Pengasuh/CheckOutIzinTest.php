<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\CheckOut;
use App\Models\Anak;
use App\Models\IzinPenjemputan;
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

class CheckOutIzinTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $siti;

    private User $orangTua;

    private Anak $anak;

    private KelompokUsia $batita;

    private Kehadiran $kehadiran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow(today()->setTime(16, 0));

        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '17:00:00',
        ]);

        $this->anak = $this->buatAnak('Aisyah Putri', $this->batita, 'aktif', $this->orangTua);
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

    private function izin(string $nama, string $status = 'disetujui', ?string $tanggal = null, ?Anak $anak = null): IzinPenjemputan
    {
        return IzinPenjemputan::create([
            'anak_id' => ($anak ?? $this->anak)->id, 'diajukan_oleh_id' => $this->orangTua->id, 'nama_penjemput' => $nama,
            'path_foto_identitas' => 'identitas/x.jpg', 'alasan' => 'Orang tua rapat', 'tanggal_jemput' => $tanggal ?? today()->toDateString(),
            'status' => $status,
        ]);
    }

    private function form(string $pilihan)
    {
        return Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $this->kehadiran->id)
            ->set('jam_pulang', '15:45')
            ->set('penjemput', $pilihan);
    }

    public function test_izin_disetujui_hari_ini_muncul_di_pilihan_penjemput(): void
    {
        $this->izin('Om Budi');
        $this->izin('Tante Ditolak', 'ditolak');
        $this->izin('Pak Kemarin', 'disetujui', today()->subDay()->toDateString());
        $this->izin('Bu Besok', 'disetujui', today()->addDay()->toDateString());
        $lain = $this->buatAnak('Anak Lain', $this->batita);
        $this->izin('Orang Anak Lain', 'disetujui', null, $lain);

        Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $this->kehadiran->id)
            ->assertSee('Om Budi (izin)')
            ->assertSee('Izin penjemputan disetujui (hari ini)')
            ->assertDontSee('Tante Ditolak')
            ->assertDontSee('Pak Kemarin')
            ->assertDontSee('Bu Besok')
            ->assertDontSee('Orang Anak Lain');
    }

    public function test_check_out_dengan_izin_menyimpan_referensi_izin_dan_nama(): void
    {
        $izin = $this->izin('Om Budi');

        $this->form(CheckOut::IZIN.$izin->id)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('Check-out Aisyah Putri berhasil dicatat. Dijemput oleh Om Budi (izin penjemputan).');

        $k = $this->kehadiran->fresh();
        $this->assertSame($izin->id, $k->izin_penjemputan_id);
        $this->assertNull($k->penjemput_terdaftar_id);
        $this->assertSame('Om Budi', $k->nama_penjemput);
        $this->assertSame('15:45', $k->check_out_pada->format('H:i'));
        $this->assertSame($this->siti->id, $k->check_out_oleh_id);
    }

    public function test_izin_yang_tidak_berlaku_ditolak_saat_simpan(): void
    {
        $ditolak = $this->izin('Tante Ditolak', 'ditolak');
        $menunggu = $this->izin('Pak Menunggu', 'menunggu');
        $kemarin = $this->izin('Pak Kemarin', 'disetujui', today()->subDay()->toDateString());
        $lain = $this->buatAnak('Anak Lain', $this->batita);
        $milikLain = $this->izin('Orang Anak Lain', 'disetujui', null, $lain);

        foreach ([$ditolak, $menunggu, $kemarin, $milikLain] as $tidakBerlaku) {
            $this->form(CheckOut::IZIN.$tidakBerlaku->id)
                ->call('simpan')
                ->assertHasErrors(['penjemput'])
                ->assertSet('tidakCocok', true);
        }

        $this->assertNull($this->kehadiran->fresh()->check_out_pada);
    }

    public function test_pilihan_izin_dengan_id_ngawur_ditolak(): void
    {
        $this->form(CheckOut::IZIN.'abc')->call('simpan')->assertHasErrors(['penjemput']);
        $this->form(CheckOut::IZIN.'99999')->call('simpan')->assertHasErrors(['penjemput']);
    }

    public function test_pesan_belum_ada_penjemput_hilang_bila_ada_izin(): void
    {
        $this->izin('Om Budi');

        Livewire::actingAs($this->siti)
            ->test(CheckOut::class)
            ->call('mulaiCheckOut', $this->kehadiran->id)
            ->assertDontSee('belum mendaftarkan penjemput');
    }
}
