<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\PendaftaranDetail;
use App\Livewire\Admin\PendaftaranIndex;
use App\Models\Anak;
use App\Models\CatatanKesehatan;
use App\Models\PaketLayanan;
use App\Models\Pendaftaran;
use App\Models\PenjemputTerdaftar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** PBI-11e: Admin melihat daftar pendaftaran yang masuk dan memeriksanya satu per satu. */
class PendaftaranIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PaketLayanan $paket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->paket = PaketLayanan::create(['nama' => 'Full Day', 'harga' => 2500000, 'aktif' => true]);
    }

    /** Membuat pendaftaran beserta anaknya tanpa memicu observer kelengkapan (status ditetapkan langsung). */
    private function buatPendaftaran(string $status, string $namaAnak, ?string $namaOrangTua = null, ?string $diajukan = null): Pendaftaran
    {
        $orangTua = User::factory()->create(array_filter(['nama' => $namaOrangTua]))->assignRole('orang_tua');

        $pendaftaran = Pendaftaran::create([
            'orang_tua_id' => $orangTua->id,
            'paket_layanan_id' => $this->paket->id,
            'status' => $status,
            'diajukan_pada' => $status === 'draft' ? null : ($diajukan ?? now()),
        ]);

        Anak::withoutEvents(fn () => Anak::create([
            'pendaftaran_id' => $pendaftaran->id,
            'orang_tua_id' => $orangTua->id,
            'nama_lengkap' => $namaAnak,
            'tanggal_lahir' => '2023-05-10',
            'jenis_kelamin' => 'P',
        ]));

        return $pendaftaran;
    }

    public function test_admin_dapat_membuka_halaman_daftar(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.pendaftaran.index'))
            ->assertOk()
            ->assertSee('Pendaftaran');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.pendaftaran.index'))->assertRedirect(route('login'));
    }

    public function test_orang_tua_dan_pengasuh_ditolak(): void
    {
        $orangTua = User::factory()->create()->assignRole('orang_tua');
        $pengasuh = User::factory()->create()->assignRole('pengasuh');
        $pendaftaran = $this->buatPendaftaran('menunggu_verifikasi', 'Aisyah');

        $this->actingAs($orangTua)->get(route('admin.pendaftaran.index'))->assertForbidden();
        $this->actingAs($pengasuh)->get(route('admin.pendaftaran.index'))->assertForbidden();
        $this->actingAs($orangTua)->get(route('admin.pendaftaran.detail', $pendaftaran))->assertForbidden();
    }

    public function test_daftar_menampilkan_pendaftaran_masuk_tanpa_draft(): void
    {
        $this->buatPendaftaran('menunggu_verifikasi', 'Aisyah Putri');
        $this->buatPendaftaran('disetujui', 'Rafi Ahmad');
        $this->buatPendaftaran('ditolak', 'Nayla Zahra');
        $this->buatPendaftaran('draft', 'Dimas Draft');

        Livewire::actingAs($this->admin)
            ->test(PendaftaranIndex::class)
            ->assertSee('Aisyah Putri')
            ->assertSee('Rafi Ahmad')
            ->assertSee('Nayla Zahra')
            ->assertDontSee('Dimas Draft');
    }

    public function test_menunggu_verifikasi_tampil_paling_atas_urut_dari_yang_terlama(): void
    {
        $this->buatPendaftaran('disetujui', 'Anak Disetujui', diajukan: '2026-11-10 08:00:00');
        $this->buatPendaftaran('menunggu_verifikasi', 'Anak Baru', diajukan: '2026-11-05 08:00:00');
        $this->buatPendaftaran('menunggu_verifikasi', 'Anak Lama', diajukan: '2026-11-01 08:00:00');

        Livewire::actingAs($this->admin)
            ->test(PendaftaranIndex::class)
            ->assertSeeInOrder(['Anak Lama', 'Anak Baru', 'Anak Disetujui']);
    }

    public function test_filter_status(): void
    {
        $this->buatPendaftaran('menunggu_verifikasi', 'Aisyah Putri');
        $this->buatPendaftaran('disetujui', 'Rafi Ahmad');

        Livewire::actingAs($this->admin)
            ->test(PendaftaranIndex::class)
            ->call('pilihStatus', 'disetujui')
            ->assertSet('status', 'disetujui')
            ->assertSee('Rafi Ahmad')
            ->assertDontSee('Aisyah Putri');
    }

    public function test_status_tidak_dikenal_atau_draft_dianggap_semua(): void
    {
        $this->buatPendaftaran('draft', 'Dimas Draft');

        Livewire::actingAs($this->admin)
            ->test(PendaftaranIndex::class)
            ->call('pilihStatus', 'draft')
            ->assertSet('status', '')
            ->assertDontSee('Dimas Draft');
    }

    public function test_pencarian_nama_anak_atau_orang_tua(): void
    {
        $this->buatPendaftaran('menunggu_verifikasi', 'Aisyah Putri', 'Budi Santoso');
        $this->buatPendaftaran('menunggu_verifikasi', 'Rafi Ahmad', 'Siti Aminah');

        Livewire::actingAs($this->admin)
            ->test(PendaftaranIndex::class)
            ->set('q', 'aisyah')
            ->assertSee('Aisyah Putri')
            ->assertDontSee('Rafi Ahmad')
            ->set('q', 'Siti')
            ->assertSee('Rafi Ahmad')
            ->assertDontSee('Aisyah Putri')
            ->call('resetFilter')
            ->assertSet('q', '')
            ->assertSee('Aisyah Putri')
            ->assertSee('Rafi Ahmad');
    }

    public function test_keadaan_kosong(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PendaftaranIndex::class)
            ->assertSee('Belum ada pendaftaran masuk');
    }

    public function test_admin_dapat_memeriksa_detail_pendaftaran(): void
    {
        $pendaftaran = $this->buatPendaftaran('menunggu_verifikasi', 'Aisyah Putri', 'Budi Santoso');
        $anak = $pendaftaran->anak;

        CatatanKesehatan::withoutEvents(fn () => CatatanKesehatan::create([
            'anak_id' => $anak->id, 'alergi' => 'Kacang tanah',
        ]));
        PenjemputTerdaftar::withoutEvents(fn () => PenjemputTerdaftar::create([
            'anak_id' => $anak->id, 'nama_lengkap' => 'Ratna Sari', 'hubungan' => 'Nenek',
            'no_hp' => '081234567890', 'path_foto_identitas' => 'penjemput/ratna.jpg', 'aktif' => true,
        ]));
        PenjemputTerdaftar::withoutEvents(fn () => PenjemputTerdaftar::create([
            'anak_id' => $anak->id, 'nama_lengkap' => 'Penjemput Nonaktif', 'hubungan' => 'Om',
            'no_hp' => '081200000000', 'path_foto_identitas' => 'penjemput/om.jpg', 'aktif' => false,
        ]));

        $this->actingAs($this->admin)
            ->get(route('admin.pendaftaran.detail', $pendaftaran))
            ->assertOk()
            ->assertSee('Periksa Pendaftaran')
            ->assertSee('Aisyah Putri')
            ->assertSee('Budi Santoso')
            ->assertSee('Full Day')
            ->assertSee('Kacang tanah')
            ->assertSee('Ratna Sari')
            ->assertDontSee('Penjemput Nonaktif');
    }

    public function test_detail_draft_tidak_bisa_dibuka_admin(): void
    {
        $draft = $this->buatPendaftaran('draft', 'Dimas Draft');

        $this->actingAs($this->admin)
            ->get(route('admin.pendaftaran.detail', $draft))
            ->assertNotFound();
    }

    public function test_daftar_menautkan_ke_halaman_periksa(): void
    {
        $pendaftaran = $this->buatPendaftaran('menunggu_verifikasi', 'Aisyah Putri');

        Livewire::actingAs($this->admin)
            ->test(PendaftaranIndex::class)
            ->assertSee(route('admin.pendaftaran.detail', $pendaftaran));

        Livewire::actingAs($this->admin)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id])
            ->assertSee('Aisyah Putri');
    }
}
