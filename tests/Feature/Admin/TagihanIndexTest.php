<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\TagihanIndex;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class TagihanIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $pengasuh;

    private User $orangTua;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
    }

    private function buatPendaftaran(string $status = 'disetujui', int $harga = 1500000): int
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket '.fake()->unique()->numberBetween(1000, 9999),
            'harga' => $harga,
            'aktif' => true,
        ]);

        return DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $this->orangTua->id,
            'paket_layanan_id' => $paket->id,
            'status' => $status,
            'diajukan_pada' => now(),
            'diverifikasi_oleh_id' => $status === 'disetujui' ? $this->admin->id : null,
            'diverifikasi_pada' => $status === 'disetujui' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buatTagihan(int $pendaftaranId, int $jumlah = 1500000, string $status = 'belum_lunas'): int
    {
        return DB::table('tagihan')->insertGetId([
            'pendaftaran_id' => $pendaftaranId,
            'nomor_tagihan' => 'TGH-'.now()->format('Ym').'-'.fake()->unique()->numberBetween(1000, 9999),
            'jumlah' => $jumlah,
            'status' => $status,
            'diterbitkan_pada' => now(),
            'jatuh_tempo' => today()->addDays(10),
            'lunas_pada' => $status === 'lunas' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buatPembayaran(int $tagihanId): void
    {
        DB::table('pembayaran')->insert([
            'tagihan_id' => $tagihanId,
            'diajukan_oleh_id' => $this->orangTua->id,
            'path_bukti' => 'pembayaran/bukti.png',
            'jumlah' => 1500000,
            'status' => 'menunggu',
            'diajukan_pada' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_dapat_menghapus_soft_delete_tagihan_belum_lunas_tanpa_pembayaran(): void
    {
        $pendaftaranId = $this->buatPendaftaran();
        $tagihanId = $this->buatTagihan($pendaftaranId);
        $nomorTagihan = DB::table('tagihan')->where('id', $tagihanId)->value('nomor_tagihan');

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusTagihan', $tagihanId)
            ->assertSet('deletingTagihanId', $tagihanId)
            ->assertSee('Hapus tagihan?')
            ->call('hapusTagihan')
            ->assertHasNoErrors()
            ->assertSee('Tagihan berhasil dihapus.');

        $this->assertNotNull(DB::table('tagihan')->where('id', $tagihanId)->value('deleted_at'));
        $this->assertStringNotContainsString($nomorTagihan, (string) Livewire::actingAs($this->admin)->test(TagihanIndex::class)->html());
    }

    public function test_admin_dapat_membatalkan_hapus_tagihan(): void
    {
        $pendaftaranId = $this->buatPendaftaran();
        $tagihanId = $this->buatTagihan($pendaftaranId);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusTagihan', $tagihanId)
            ->call('batalHapusTagihan')
            ->assertSet('deletingTagihanId', null)
            ->assertSet('deletingTagihanNomor', '')
            ->assertSee('Daftar Tagihan');

        $this->assertDatabaseHas('tagihan', [
            'id' => $tagihanId,
            'deleted_at' => null,
        ]);
    }

    public function test_tagihan_lunas_tidak_dapat_dihapus(): void
    {
        $pendaftaranId = $this->buatPendaftaran();
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000, 'lunas');

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusTagihan', $tagihanId)
            ->assertStatus(404);
    }

    public function test_tagihan_yang_memiliki_pembayaran_tidak_dapat_dihapus(): void
    {
        $pendaftaranId = $this->buatPendaftaran();
        $tagihanId = $this->buatTagihan($pendaftaranId);
        $this->buatPembayaran($tagihanId);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusTagihan', $tagihanId)
            ->assertStatus(404);
    }

    public function test_admin_dapat_mengubah_nominal_tagihan_belum_lunas(): void
    {
        $pendaftaranId = $this->buatPendaftaran();
        $tagihanId = $this->buatTagihan($pendaftaranId);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('mulaiEditTagihan', $tagihanId)
            ->assertSet('editingTagihanId', $tagihanId)
            ->assertSet('editJumlah', '1500000.00')
            ->assertSee('Ubah nominal tagihan')
            ->set('editJumlah', '1750000')
            ->call('simpanEditTagihan')
            ->assertHasNoErrors()
            ->assertSee('Nominal tagihan berhasil diperbarui.');

        $this->assertDatabaseHas('tagihan', [
            'id' => $tagihanId,
            'jumlah' => '1750000.00',
            'status' => 'belum_lunas',
        ]);
    }

    public function test_admin_dapat_membatalkan_edit_nominal(): void
    {
        $pendaftaranId = $this->buatPendaftaran();
        $tagihanId = $this->buatTagihan($pendaftaranId);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('mulaiEditTagihan', $tagihanId)
            ->set('editJumlah', '1750000')
            ->call('batalEditTagihan')
            ->assertSet('editingTagihanId', null)
            ->assertSet('editJumlah', '')
            ->assertSee('Daftar Tagihan');

        $this->assertDatabaseHas('tagihan', [
            'id' => $tagihanId,
            'jumlah' => '1500000.00',
        ]);
    }

    public function test_nominal_tagihan_harus_valid(): void
    {
        $pendaftaranId = $this->buatPendaftaran();
        $tagihanId = $this->buatTagihan($pendaftaranId);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('mulaiEditTagihan', $tagihanId)
            ->set('editJumlah', '0')
            ->call('simpanEditTagihan')
            ->assertHasErrors(['editJumlah']);

        $this->assertDatabaseHas('tagihan', [
            'id' => $tagihanId,
            'jumlah' => '1500000.00',
        ]);
    }

    public function test_tagihan_lunas_tidak_dapat_diubah_nominalnya(): void
    {
        $pendaftaranId = $this->buatPendaftaran();
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000, 'lunas');

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('mulaiEditTagihan', $tagihanId)
            ->assertStatus(404);
    }

    public function test_admin_dapat_melihat_pendaftaran_yang_disetujui(): void
    {
        $disetujui = $this->buatPendaftaran();
        $menunggu = $this->buatPendaftaran('menunggu_verifikasi');

        $this->actingAs($this->admin)
            ->get(route('admin.tagihan.index'))
            ->assertOk()
            ->assertSee('Terbitkan Tagihan')
            ->assertSee('Pendaftaran #'.$disetujui)
            ->assertDontSee('Pendaftaran #'.$menunggu);
    }

    public function test_admin_dapat_melihat_daftar_tagihan_dengan_statusnya(): void
    {
        $belumLunas = $this->buatPendaftaran('disetujui', 1500000);
        $lunas = $this->buatPendaftaran('disetujui', 2750000);

        DB::table('tagihan')->insert([
            [
                'pendaftaran_id' => $belumLunas,
                'nomor_tagihan' => 'TGH-'.now()->format('Ym').'-0001',
                'jumlah' => 1500000,
                'status' => 'belum_lunas',
                'diterbitkan_pada' => now()->subDay(),
                'jatuh_tempo' => today()->addDays(9),
                'lunas_pada' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'pendaftaran_id' => $lunas,
                'nomor_tagihan' => 'TGH-'.now()->format('Ym').'-0002',
                'jumlah' => 2750000,
                'status' => 'lunas',
                'diterbitkan_pada' => now()->subDays(3),
                'jatuh_tempo' => today()->addDays(7),
                'lunas_pada' => now()->subDay(),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.tagihan.index'))
            ->assertOk()
            ->assertSee('Daftar Tagihan')
            ->assertSee('TGH-'.now()->format('Ym').'-0001')
            ->assertSee('TGH-'.now()->format('Ym').'-0002')
            ->assertSee('Belum Lunas')
            ->assertSee('Lunas')
            ->assertSee('Rp 1.500.000,00')
            ->assertSee('Rp 2.750.000,00');
    }

    public function test_tagihan_soft_deleted_tidak_tampil_di_daftar(): void
    {
        $pendaftaranId = $this->buatPendaftaran();

        DB::table('tagihan')->insert([
            'pendaftaran_id' => $pendaftaranId,
            'nomor_tagihan' => 'TGH-'.now()->format('Ym').'-9999',
            'jumlah' => 1500000,
            'status' => 'belum_lunas',
            'diterbitkan_pada' => now(),
            'jatuh_tempo' => today()->addDays(10),
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.tagihan.index'))
            ->assertOk()
            ->assertSee('Daftar Tagihan')
            ->assertDontSee('TGH-'.now()->format('Ym').'-9999');
    }

    public function test_admin_dapat_menerbitkan_tagihan_sesuai_tarif_paket(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 2750000);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiPenerbitan', $pendaftaranId)
            ->assertSet('penerbitanId', $pendaftaranId)
            ->assertSee('Terbitkan tagihan?')
            ->call('terbitkan')
            ->assertHasNoErrors()
            ->assertSee('Tagihan berhasil diterbitkan.');

        $this->assertDatabaseHas('tagihan', [
            'pendaftaran_id' => $pendaftaranId,
            'jumlah' => '2750000.00',
            'status' => 'belum_lunas',
        ]);

        $tagihan = DB::table('tagihan')->where('pendaftaran_id', $pendaftaranId)->first();

        $this->assertNotNull($tagihan);
        $this->assertSame(today()->addDays(10)->toDateString(), substr((string) $tagihan->jatuh_tempo, 0, 10));
        $this->assertMatchesRegularExpression('/^TGH-'.now()->format('Ym').'\-\d{4}$/', $tagihan->nomor_tagihan);
        $this->assertNotNull($tagihan->diterbitkan_pada);
    }

    public function test_setiap_penerbitan_menghasilkan_nomor_tagihan_unik(): void
    {
        $pertama = $this->buatPendaftaran();
        $kedua = $this->buatPendaftaran();

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiPenerbitan', $pertama)
            ->call('terbitkan')
            ->call('bukaKonfirmasiPenerbitan', $kedua)
            ->call('terbitkan')
            ->assertHasNoErrors();

        $nomor = DB::table('tagihan')->pluck('nomor_tagihan');

        $this->assertCount(2, $nomor->unique());
    }

    public function test_admin_dapat_membatalkan_penerbitan(): void
    {
        $pendaftaranId = $this->buatPendaftaran();

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiPenerbitan', $pendaftaranId)
            ->call('batalPenerbitan')
            ->assertSet('penerbitanId', null)
            ->assertSet('penerbitanNama', '')
            ->assertSee('Terbitkan tagihan');

        $this->assertDatabaseCount('tagihan', 0);
    }

    public function test_pendaftaran_yang_belum_disetujui_tidak_dapat_diterbitkan(): void
    {
        $pendaftaranId = $this->buatPendaftaran('menunggu_verifikasi');

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiPenerbitan', $pendaftaranId)
            ->assertStatus(404);
    }

    public function test_tamu_ditolak_oleh_middleware_auth(): void
    {
        $this->get(route('admin.tagihan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $this->actingAs($this->pengasuh)
            ->get(route('admin.tagihan.index'))
            ->assertForbidden();
    }
}
