<?php

namespace Tests\Feature\OrangTua;

use App\Livewire\OrangTua\PendaftaranBuat;
use App\Livewire\OrangTua\PendaftaranIndex;
use App\Models\PaketLayanan;
use App\Models\Pendaftaran;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendaftaranTest extends TestCase
{
    use RefreshDatabase;

    private User $orangTua;

    private User $admin;

    private PaketLayanan $paket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->paket = PaketLayanan::create([
            'nama' => 'Full Day', 'deskripsi' => 'Senin sampai Jumat.', 'harga' => 2500000, 'aktif' => true,
        ]);
    }

    public function test_orang_tua_dapat_membuka_halaman_buat(): void
    {
        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.pendaftaran.buat'))
            ->assertOk()
            ->assertSee('Buat Pendaftaran')
            ->assertSee('Full Day');
    }

    public function test_admin_tidak_boleh_membuat_pendaftaran_orang_tua(): void
    {
        $this->actingAs($this->admin)
            ->get(route('orang-tua.pendaftaran.buat'))
            ->assertForbidden();
    }

    public function test_orang_tua_membuat_pendaftaran_dengan_status_draft(): void
    {
        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranBuat::class)
            ->set('paket_layanan_id', (string) $this->paket->id)
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertRedirect(route('orang-tua.pendaftaran.index'));

        $this->assertDatabaseHas('pendaftaran', [
            'orang_tua_id' => $this->orangTua->id,
            'paket_layanan_id' => $this->paket->id,
            'status' => 'draft',
            'diajukan_pada' => null,
            'diverifikasi_oleh_id' => null,
        ]);
    }

    public function test_paket_layanan_wajib_dipilih(): void
    {
        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranBuat::class)
            ->set('paket_layanan_id', '')
            ->call('simpan')
            ->assertHasErrors(['paket_layanan_id']);

        $this->assertDatabaseCount('pendaftaran', 0);
    }

    public function test_paket_nonaktif_tidak_bisa_dipilih(): void
    {
        $nonaktif = PaketLayanan::create([
            'nama' => 'Fleksibel Harian', 'deskripsi' => 'Bayar per hari.', 'harga' => 175000, 'aktif' => false,
        ]);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranBuat::class)
            ->set('paket_layanan_id', (string) $nonaktif->id)
            ->call('simpan')
            ->assertHasErrors(['paket_layanan_id']);

        $this->assertDatabaseCount('pendaftaran', 0);
    }

    public function test_daftar_hanya_menampilkan_pendaftaran_milik_sendiri(): void
    {
        $lainnya = User::factory()->create()->assignRole('orang_tua');
        $paketLain = PaketLayanan::create([
            'nama' => 'Weekend Care', 'deskripsi' => 'Sabtu dan Minggu.', 'harga' => 1000000, 'aktif' => true,
        ]);

        Pendaftaran::create(['orang_tua_id' => $this->orangTua->id, 'paket_layanan_id' => $this->paket->id, 'status' => 'draft']);
        Pendaftaran::create(['orang_tua_id' => $lainnya->id, 'paket_layanan_id' => $paketLain->id, 'status' => 'draft']);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranIndex::class)
            ->assertSee('Full Day')
            ->assertDontSee('Weekend Care');
    }

    public function test_status_pendaftaran_ditampilkan_dengan_label(): void
    {
        Pendaftaran::create(['orang_tua_id' => $this->orangTua->id, 'paket_layanan_id' => $this->paket->id, 'status' => 'draft']);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.pendaftaran.index'))
            ->assertOk()
            ->assertSee('Pendaftaran Saya')
            ->assertSee('Draft');
    }

    public function test_daftar_kosong_menampilkan_pesan(): void
    {
        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.pendaftaran.index'))
            ->assertOk()
            ->assertSee('Belum ada pendaftaran.');
    }
}
