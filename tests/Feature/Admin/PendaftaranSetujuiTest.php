<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\PendaftaranDetail;
use App\Models\Anak;
use App\Models\KelompokUsia;
use App\Models\PaketLayanan;
use App\Models\Pendaftaran;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** PBI-11f: Admin menyetujui pendaftaran dan menetapkan kelompok usia agar anak berstatus aktif. */
class PendaftaranSetujuiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PaketLayanan $paket;

    private KelompokUsia $batita;

    private KelompokUsia $toddler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['nama' => 'Admin Daycare'])->assignRole('admin');
        $this->paket = PaketLayanan::create(['nama' => 'Full Day', 'harga' => 2500000, 'aktif' => true]);
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        $this->toddler = KelompokUsia::create(['nama' => 'Toddler', 'usia_min_bulan' => 24, 'usia_maks_bulan' => 35, 'kapasitas' => 15]);
    }

    /** Pendaftaran beserta anak berusia $usiaBulan, tanpa memicu observer kelengkapan. */
    private function buatPendaftaran(string $status = 'menunggu_verifikasi', int $usiaBulan = 18, string $namaAnak = 'Zidan Alfarizi'): Pendaftaran
    {
        $orangTua = User::factory()->create()->assignRole('orang_tua');

        $pendaftaran = Pendaftaran::create([
            'orang_tua_id' => $orangTua->id,
            'paket_layanan_id' => $this->paket->id,
            'status' => $status,
            'diajukan_pada' => $status === 'draft' ? null : now()->subDay(),
        ]);

        Anak::withoutEvents(fn () => Anak::create([
            'pendaftaran_id' => $pendaftaran->id,
            'orang_tua_id' => $orangTua->id,
            'nama_lengkap' => $namaAnak,
            'tanggal_lahir' => today()->subMonths($usiaBulan)->subDays(3),
            'jenis_kelamin' => 'L',
            'status' => $status === 'disetujui' ? 'aktif' : 'menunggu',
        ]));

        return $pendaftaran;
    }

    /** Mengisi kelompok usia dengan $jumlah anak aktif. */
    private function isiKelompok(KelompokUsia $kelompok, int $jumlah): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            $pendaftaran = $this->buatPendaftaran('disetujui', namaAnak: "Anak Aktif {$i}");
            $pendaftaran->anak->update(['kelompok_usia_id' => $kelompok->id]);
        }
    }

    public function test_admin_menyetujui_dan_anak_menjadi_aktif_di_kelompok_usia(): void
    {
        $this->freezeSecond();
        $pendaftaran = $this->buatPendaftaran();

        Livewire::actingAs($this->admin)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id])
            ->set('kelompok_usia_id', (string) $this->toddler->id)
            ->call('setujui')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.pendaftaran.index'));

        $pendaftaran->refresh();
        $anak = $pendaftaran->anak;

        $this->assertSame('disetujui', $pendaftaran->status);
        $this->assertSame($this->admin->id, $pendaftaran->diverifikasi_oleh_id);
        $this->assertTrue($pendaftaran->diverifikasi_pada->equalTo(now()));
        $this->assertSame('aktif', $anak->status);
        $this->assertSame($this->toddler->id, $anak->kelompok_usia_id);

        $this->assertSame(
            'Pendaftaran Zidan Alfarizi disetujui. Anak sekarang aktif di kelompok Toddler.',
            session('sukses')
        );
    }

    public function test_kelompok_usia_otomatis_dipilihkan_sesuai_usia_anak(): void
    {
        $pendaftaran = $this->buatPendaftaran(usiaBulan: 18);

        Livewire::actingAs($this->admin)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id])
            ->assertSet('kelompok_usia_id', (string) $this->batita->id)
            ->assertSee('Persetujuan')
            ->assertSee('Setujui pendaftaran')
            ->assertSee('(sesuai usia)');
    }

    public function test_kelompok_usia_wajib_dipilih(): void
    {
        $pendaftaran = $this->buatPendaftaran(usiaBulan: 90); // tidak cocok dengan kelompok mana pun

        Livewire::actingAs($this->admin)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id])
            ->assertSet('kelompok_usia_id', '')
            ->call('setujui')
            ->assertHasErrors(['kelompok_usia_id' => 'required'])
            ->assertSee('Pilih kelompok usia anak terlebih dahulu.');

        $this->assertSame('menunggu_verifikasi', $pendaftaran->fresh()->status);
        $this->assertSame('menunggu', $pendaftaran->anak->fresh()->status);
    }

    public function test_kelompok_usia_yang_tidak_ada_ditolak(): void
    {
        $pendaftaran = $this->buatPendaftaran();

        Livewire::actingAs($this->admin)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id])
            ->set('kelompok_usia_id', '9999')
            ->call('setujui')
            ->assertHasErrors(['kelompok_usia_id' => 'exists']);

        $this->assertSame('menunggu_verifikasi', $pendaftaran->fresh()->status);
    }

    public function test_kelompok_usia_yang_penuh_tidak_bisa_dipilih(): void
    {
        $this->batita->update(['kapasitas' => 2]);
        $this->isiKelompok($this->batita, 2);
        $pendaftaran = $this->buatPendaftaran(usiaBulan: 18);

        Livewire::actingAs($this->admin)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id])
            ->assertSet('kelompok_usia_id', '') // kelompok yang cocok sudah penuh, jadi tidak dipilihkan
            ->set('kelompok_usia_id', (string) $this->batita->id)
            ->call('setujui')
            ->assertHasErrors('kelompok_usia_id')
            ->assertSee('sudah penuh');

        $this->assertSame('menunggu_verifikasi', $pendaftaran->fresh()->status);
        $this->assertNull($pendaftaran->anak->fresh()->kelompok_usia_id);
    }

    public function test_pendaftaran_yang_sudah_diproses_tidak_menampilkan_form_persetujuan(): void
    {
        foreach (['disetujui', 'ditolak'] as $status) {
            $pendaftaran = $this->buatPendaftaran($status);

            Livewire::actingAs($this->admin)
                ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id])
                ->assertDontSee('Setujui pendaftaran');
        }
    }

    public function test_tidak_bisa_disetujui_dua_kali(): void
    {
        $pendaftaran = $this->buatPendaftaran();

        $halaman = Livewire::actingAs($this->admin)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id]);

        // Diproses lebih dulu dari tab lain.
        $pendaftaran->setujui($this->batita, $this->admin);

        $halaman->set('kelompok_usia_id', (string) $this->toddler->id)
            ->call('setujui')
            ->assertHasErrors('kelompok_usia_id')
            ->assertNoRedirect();

        $this->assertSame($this->batita->id, $pendaftaran->anak->fresh()->kelompok_usia_id);
    }

    public function test_model_tidak_menyetujui_selain_menunggu_verifikasi(): void
    {
        $draft = $this->buatPendaftaran('draft');

        $this->assertFalse($draft->setujui($this->batita, $this->admin));
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('menunggu', $draft->anak->fresh()->status);
        $this->assertNull($draft->anak->fresh()->kelompok_usia_id);
    }

    public function test_hanya_pemilik_permission_yang_bisa_menyetujui(): void
    {
        $pendaftaran = $this->buatPendaftaran();
        $orangTua = User::factory()->create()->assignRole('orang_tua');

        $this->actingAs($orangTua)
            ->get(route('admin.pendaftaran.detail', $pendaftaran))
            ->assertForbidden();

        $halaman = Livewire::actingAs($this->admin)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $pendaftaran->id]);

        $this->admin->syncRoles(['orang_tua']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $halaman->set('kelompok_usia_id', (string) $this->batita->id)
            ->call('setujui')
            ->assertForbidden();

        $this->assertSame('menunggu_verifikasi', $pendaftaran->fresh()->status);
    }

    public function test_pendaftaran_disetujui_tampil_di_daftar_dan_halaman_orang_tua(): void
    {
        $pendaftaran = $this->buatPendaftaran();
        $pendaftaran->setujui($this->batita, $this->admin);

        $this->actingAs($this->admin)
            ->get(route('admin.pendaftaran.detail', $pendaftaran))
            ->assertOk()
            ->assertSee('Disetujui')
            ->assertSee('Admin Daycare')
            ->assertSee('Batita');

        $this->actingAs($pendaftaran->orangTua)
            ->get(route('orang-tua.pendaftaran.detail', $pendaftaran))
            ->assertOk()
            ->assertSee('Disetujui');
    }
}
