<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\PaketLayananIndex;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PaketLayananIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pengasuh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
    }

    public function test_admin_dapat_membuka_halaman_paket_layanan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.paket-layanan.index'))
            ->assertOk()
            ->assertSee('Paket Layanan')
            ->assertSee('Tambah paket')
            ->assertSee(route('admin.paket-layanan.create'));
    }

    public function test_admin_dapat_membuka_modal_hapus_dengan_markup_yang_dapat_diakses(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Dialog',
            'harga' => 100000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('bukaKonfirmasiHapus', $paket->id)
            ->assertSet('deletingId', $paket->id)
            ->assertSet('deletingName', 'Paket Dialog')
            ->assertSee('Ya, hapus permanen')
            ->assertSee('Batal')
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-labelledby=', false)
            ->assertSee('aria-describedby=', false)
            ->assertSee('Escape', false)
            ->assertSee('x-data=', false)
            ->assertSee('x-on:keydown=', false)
            ->assertSee('focusables()', false)
            ->assertSee('restoreFocus()', false);
    }

    public function test_admin_dapat_membatalkan_modal_hapus(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Batal Hapus',
            'harga' => 100000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('bukaKonfirmasiHapus', $paket->id)
            ->call('batalHapus')
            ->assertSet('deletingId', null)
            ->assertSet('deletingName', null)
            ->assertHasNoErrors();
    }

    public function test_admin_dapat_menghapus_fisik_paket_tidak_terpakai(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Hapus',
            'harga' => 100000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('bukaKonfirmasiHapus', $paket->id)
            ->call('hapus')
            ->assertSet('deletingId', null)
            ->assertSet('deletingName', null)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('paket_layanan', ['id' => $paket->id]);
    }

    public function test_admin_dapat_menghapus_paket_aktif_yang_tidak_terpakai(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Aktif Hapus',
            'harga' => 100000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->set('deletingId', $paket->id)
            ->call('hapus')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('paket_layanan', ['id' => $paket->id]);
    }

    public function test_paket_yang_memiliki_pendaftaran_tidak_dapat_dihapus(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Terpakai',
            'harga' => 100000,
            'aktif' => true,
        ]);
        $this->buatPendaftaran($paket->id);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('bukaKonfirmasiHapus', $paket->id)
            ->call('hapus')
            ->assertSet('deletingId', $paket->id)
            ->assertHasErrors(['hapus'])
            ->assertSee('tidak dapat dihapus');

        $this->assertDatabaseHas('paket_layanan', ['id' => $paket->id]);
    }

    public function test_paket_yang_memiliki_pendaftaran_terhapus_tetap_tidak_dapat_dihapus(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Soft Delete',
            'harga' => 100000,
            'aktif' => true,
        ]);
        $this->buatPendaftaran($paket->id, now());

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('bukaKonfirmasiHapus', $paket->id)
            ->call('hapus')
            ->assertSet('deletingId', $paket->id)
            ->assertHasErrors(['hapus']);

        $this->assertDatabaseHas('paket_layanan', ['id' => $paket->id]);
    }

    public function test_pengguna_tanpa_permission_tidak_dapat_menghapus(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Hapus Terproteksi',
            'harga' => 100000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->pengasuh)
            ->test(PaketLayananIndex::class)
            ->call('bukaKonfirmasiHapus', $paket->id)
            ->assertForbidden();

        Livewire::actingAs($this->pengasuh)
            ->test(PaketLayananIndex::class)
            ->set('deletingId', $paket->id)
            ->call('hapus')
            ->assertForbidden();
    }

    public function test_id_hapus_yang_hilang_tidak_menghapus_paket_lain(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Tetap Aman',
            'harga' => 100000,
            'aktif' => true,
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->set('deletingId', 999999);

        $component->call('hapus');

        $this->assertDatabaseHas('paket_layanan', ['id' => $paket->id]);
    }

    public function test_menghapus_paket_yang_sedang_diedit_membersihkan_state_edit(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Edit Lalu Hapus',
            'deskripsi' => 'Deskripsi.',
            'harga' => 100000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('mulaiEdit', $paket->id)
            ->call('bukaKonfirmasiHapus', $paket->id)
            ->call('hapus')
            ->assertSet('editingId', null)
            ->assertSet('editDeskripsi', '')
            ->assertSet('editHarga', '')
            ->assertSet('editAktif', true)
            ->assertSet('deletingId', null)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('paket_layanan', ['id' => $paket->id]);
    }

    public function test_konfirmasi_hapus_paket_yang_sudah_hilang_menutup_modal_secara_anggun(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Sudah Hilang',
            'harga' => 100000,
            'aktif' => true,
        ]);

        $test = Livewire::actingAs($this->admin)->test(PaketLayananIndex::class);
        $test->call('bukaKonfirmasiHapus', $paket->id);
        // Simulasi: paket dihapus oleh admin lain antara buka modal dan konfirmasi.
        PaketLayanan::whereKey($paket->id)->delete();

        $test->call('hapus')
            ->assertSet('deletingId', null)
            ->assertSet('deletingName', null)
            ->assertHasNoErrors();
    }

    private function buatPendaftaran(int $paketId, ?Carbon $deletedAt = null): void
    {
        DB::table('pendaftaran')->insert([
            'orang_tua_id' => $this->pengasuh->id,
            'paket_layanan_id' => $paketId,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }

    public function test_tamu_ditolak_oleh_middleware_auth(): void
    {
        $this->get(route('admin.paket-layanan.index'))
            ->assertStatus(302);
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $this->actingAs($this->pengasuh)
            ->get(route('admin.paket-layanan.index'))
            ->assertForbidden();
    }

    public function test_admin_dapat_mengubah_status_paket_ke_nonaktif_dan_mengaktifkannya_kembali(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Toggle',
            'deskripsi' => null,
            'harga' => 200000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('toggleAktif', $paket->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('paket_layanan', [
            'id' => $paket->id,
            'aktif' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('toggleAktif', $paket->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('paket_layanan', [
            'id' => $paket->id,
            'aktif' => true,
        ]);
    }

    public function test_toggle_tidak_menghapus_data_paket(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Tetap Ada',
            'deskripsi' => 'Tetap tersimpan.',
            'harga' => 175000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('toggleAktif', $paket->id);

        $this->assertDatabaseHas('paket_layanan', [
            'id' => $paket->id,
            'nama' => 'Paket Tetap Ada',
            'deskripsi' => 'Tetap tersimpan.',
            'harga' => '175000.00',
        ]);
    }

    public function test_admin_dapat_membuka_mode_edit_paket(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Edit',
            'deskripsi' => 'Deskripsi lama.',
            'harga' => 250000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('mulaiEdit', $paket->id)
            ->assertSet('editingId', $paket->id)
            ->assertSet('editDeskripsi', 'Deskripsi lama.')
            ->assertSet('editHarga', '250000.00')
            ->assertSet('editAktif', true);
    }

    public function test_admin_dapat_mengubah_tarif_deskripsi_dan_status_tanpa_mengubah_nama(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Nama Tetap',
            'deskripsi' => 'Deskripsi lama.',
            'harga' => 250000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('mulaiEdit', $paket->id)
            ->set('editDeskripsi', 'Deskripsi baru.')
            ->set('editHarga', '375000')
            ->set('editAktif', false)
            ->call('simpanEdit')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $this->assertDatabaseHas('paket_layanan', [
            'id' => $paket->id,
            'nama' => 'Nama Tetap',
            'deskripsi' => 'Deskripsi baru.',
            'harga' => '375000.00',
            'aktif' => false,
        ]);
    }

    public function test_edit_menolak_tarif_tidak_valid_dan_tidak_mengubah_data(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Validasi Edit',
            'deskripsi' => 'Lama',
            'harga' => 200000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('mulaiEdit', $paket->id)
            ->set('editHarga', '0')
            ->call('simpanEdit')
            ->assertHasErrors(['editHarga']);

        $this->assertDatabaseHas('paket_layanan', [
            'id' => $paket->id,
            'harga' => '200000.00',
        ]);
    }

    public function test_edit_menyimpan_deskripsi_kosong_sebagai_null(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Tanpa Deskripsi Edit',
            'deskripsi' => 'Deskripsi lama',
            'harga' => 100000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('mulaiEdit', $paket->id)
            ->set('editDeskripsi', '')
            ->call('simpanEdit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('paket_layanan', [
            'id' => $paket->id,
            'deskripsi' => null,
        ]);
    }

    public function test_admin_dapat_membatalkan_edit_tanpa_menyimpan_perubahan(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Batal',
            'deskripsi' => 'Tetap lama',
            'harga' => 200000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PaketLayananIndex::class)
            ->call('mulaiEdit', $paket->id)
            ->set('editDeskripsi', 'Perubahan sementara')
            ->call('batalEdit')
            ->assertSet('editingId', null);

        $this->assertDatabaseHas('paket_layanan', [
            'id' => $paket->id,
            'deskripsi' => 'Tetap lama',
        ]);
    }

    public function test_pengguna_tanpa_permission_tidak_dapat_mengedit_paket(): void
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket Terproteksi',
            'deskripsi' => null,
            'harga' => 100000,
            'aktif' => true,
        ]);

        Livewire::actingAs($this->pengasuh)
            ->test(PaketLayananIndex::class)
            ->call('mulaiEdit', $paket->id)
            ->assertForbidden();
    }
}
