<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RekapKehadiran;
use App\Livewire\Pengasuh\Presensi;
use App\Models\Anak;
use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\LogPenghapusanKehadiran;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class KehadiranHapusTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $admin;

    private User $siti;

    private KelompokUsia $batita;

    private Anak $anak;

    private Kehadiran $kehadiran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->anak = $this->buatAnak('Aisyah Putri', $this->batita);
        $this->kehadiran = $this->hadir($this->anak);
    }

    private function hadir(Anak $anak): Kehadiran
    {
        $tanggal = today()->toDateString();

        return Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal 07:30:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $this->siti->id, 'check_out_pada' => "$tanggal 16:00:00", 'check_out_oleh_id' => $this->siti->id,
            'nama_penjemput' => 'Ibu Aisyah',
        ]);
    }

    private function dialog(?int $id = null)
    {
        return Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->call('konfirmasiHapus', $id ?? $this->kehadiran->id);
    }

    public function test_hapus_menghilangkan_kehadiran_dan_menutup_dialog(): void
    {
        $lain = $this->hadir($this->buatAnak('Bima Pratama', $this->batita));

        $this->dialog()
            ->set('alasan', 'Salah memilih anak saat check-in')
            ->call('hapus')
            ->assertHasNoErrors()
            ->assertSet('hapusId', null)
            ->assertSet('alasan', '')
            ->assertSee('Data kehadiran Aisyah Putri tanggal '.today()->format('d/m/Y').' berhasil dihapus.');

        $this->assertDatabaseMissing('kehadiran', ['id' => $this->kehadiran->id]);
        $this->assertDatabaseHas('kehadiran', ['id' => $lain->id]);
    }

    public function test_penghapusan_dicatat_lengkap_dengan_salinan_data(): void
    {
        $this->dialog()->set('alasan', '  Salah memilih anak saat check-in  ')->call('hapus');

        $log = LogPenghapusanKehadiran::sole();
        $this->assertSame($this->kehadiran->id, $log->kehadiran_id);
        $this->assertSame($this->anak->id, $log->anak_id);
        $this->assertTrue($log->tanggal->isToday());
        $this->assertSame('Salah memilih anak saat check-in', $log->alasan);
        $this->assertSame($this->admin->id, $log->dihapus_oleh_id);
        $this->assertNotNull($log->dihapus_pada);
        $this->assertSame('Sehat', $log->data['kondisi_check_in']);
        $this->assertSame('Ibu Aisyah', $log->data['nama_penjemput']);
        $this->assertSame($this->siti->id, $log->data['check_in_oleh_id']);
        $this->assertArrayNotHasKey('anak', $log->data);
        $this->assertSame('Aisyah Putri', $log->anak->nama_lengkap);
        $this->assertSame($this->admin->id, $log->dihapusOleh->id);
    }

    public function test_alasan_wajib_dan_minimal_lima_karakter(): void
    {
        $this->dialog()->set('alasan', '')->call('hapus')->assertHasErrors(['alasan']);
        $this->dialog()->set('alasan', 'abc')->call('hapus')->assertHasErrors(['alasan']);
        $this->dialog()->set('alasan', str_repeat('a', 256))->call('hapus')->assertHasErrors(['alasan']);

        $this->assertDatabaseHas('kehadiran', ['id' => $this->kehadiran->id]);
        $this->assertDatabaseCount('log_penghapusan_kehadiran', 0);
    }

    public function test_dialog_tetap_terbuka_saat_alasan_belum_valid(): void
    {
        $this->dialog()
            ->call('hapus')
            ->assertSet('hapusId', $this->kehadiran->id)
            ->assertSee('Alasan penghapusan');
    }

    public function test_hapus_tanpa_konfirmasi_tidak_menghapus_apa_pun(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->set('alasan', 'Alasan yang cukup panjang')
            ->call('hapus')
            ->assertSee('sudah tidak ada');

        $this->assertDatabaseHas('kehadiran', ['id' => $this->kehadiran->id]);
        $this->assertDatabaseCount('log_penghapusan_kehadiran', 0);
    }

    public function test_kehadiran_yang_sudah_terhapus_tidak_menimbulkan_error(): void
    {
        $dialog = $this->dialog()->set('alasan', 'Alasan yang cukup panjang');

        $this->kehadiran->delete();

        $dialog->call('hapus')->assertSet('hapusId', null)->assertSee('Data kehadiran sudah tidak ada.');
        $this->assertDatabaseCount('log_penghapusan_kehadiran', 0);
    }

    public function test_id_kehadiran_tidak_bisa_diubah_dari_klien(): void
    {
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        $this->dialog()->set('hapusId', 12345);
    }

    public function test_pengguna_tanpa_izin_tidak_bisa_menghapus(): void
    {
        $dialog = $this->dialog()->set('alasan', 'Alasan yang cukup panjang');

        $this->admin->roles->each->revokePermissionTo('hapus data kehadiran');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $dialog->call('hapus')->assertForbidden();

        $this->assertDatabaseHas('kehadiran', ['id' => $this->kehadiran->id]);
    }

    public function test_anak_dapat_dicatat_ulang_setelah_data_salah_dihapus(): void
    {
        JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => today()->dayOfWeekIso,
            'jam_mulai' => '00:00:00', 'jam_selesai' => '23:59:00',
        ]);

        $this->dialog()->set('alasan', 'Salah input jam datang')->call('hapus');

        Livewire::actingAs($this->siti)->test(Presensi::class)
            ->call('mulaiCheckIn', $this->anak->id)
            ->assertSet('anakId', $this->anak->id)
            ->set('jam_datang', '00:00')
            ->set('kondisi', 'Sehat')
            ->call('simpan')
            ->assertSee('Check-in Aisyah Putri berhasil dicatat.');

        $this->assertDatabaseCount('kehadiran', 1);
        $this->assertDatabaseCount('log_penghapusan_kehadiran', 1);
    }
}
