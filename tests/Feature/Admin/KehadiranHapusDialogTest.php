<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RekapKehadiran;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MembuatAnak;
use Tests\TestCase;

class KehadiranHapusDialogTest extends TestCase
{
    use MembuatAnak, RefreshDatabase;

    private User $admin;

    private Kehadiran $kehadiran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $siti = User::factory()->create()->assignRole('pengasuh');
        $batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $anak = $this->buatAnak('Aisyah Putri', $batita);
        $tanggal = today()->toDateString();
        $this->kehadiran = Kehadiran::create([
            'anak_id' => $anak->id, 'tanggal' => $tanggal, 'check_in_pada' => "$tanggal 07:30:00", 'kondisi_check_in' => 'Sehat',
            'check_in_oleh_id' => $siti->id, 'check_out_pada' => "$tanggal 16:00:00", 'check_out_oleh_id' => $siti->id, 'nama_penjemput' => 'Ibu',
        ]);
    }

    public function test_admin_melihat_tombol_hapus_di_rekap(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->assertSee('Hapus kehadiran Aisyah Putri');
    }

    public function test_dialog_menampilkan_detail_kehadiran_dan_kolom_alasan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->assertDontSee('Hapus data kehadiran?')
            ->call('konfirmasiHapus', $this->kehadiran->id)
            ->assertSet('hapusId', $this->kehadiran->id)
            ->assertSee('Hapus data kehadiran?')
            ->assertSee('datang 07:30, pulang 16:00')
            ->assertSee('Alasan penghapusan')
            ->assertSee('Ya, hapus');
    }

    public function test_batal_menutup_dialog_dan_mengosongkan_alasan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->call('konfirmasiHapus', $this->kehadiran->id)
            ->set('alasan', 'Salah input')
            ->call('batalHapus')
            ->assertSet('hapusId', null)
            ->assertSet('alasan', '')
            ->assertDontSee('Hapus data kehadiran?');

        $this->assertDatabaseHas('kehadiran', ['id' => $this->kehadiran->id]);
    }

    public function test_id_tidak_dikenal_tidak_membuka_dialog(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RekapKehadiran::class)
            ->call('konfirmasiHapus', 9999)
            ->assertSet('hapusId', null);
    }

    public function test_pengguna_tanpa_izin_hapus_tidak_melihat_tombol_dan_tidak_bisa_membuka_dialog(): void
    {
        $this->admin->revokePermissionTo('hapus data kehadiran');
        $this->admin->roles->each->revokePermissionTo('hapus data kehadiran');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Livewire::actingAs($this->admin->fresh())
            ->test(RekapKehadiran::class)
            ->assertDontSee('Hapus kehadiran Aisyah Putri')
            ->call('konfirmasiHapus', $this->kehadiran->id)
            ->assertForbidden();
    }
}
