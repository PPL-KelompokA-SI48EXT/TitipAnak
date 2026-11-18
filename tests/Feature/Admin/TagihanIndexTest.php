<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\TagihanIndex;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    private function buatPembayaran(int $tagihanId, string $status = 'menunggu', int $jumlah = 1500000, string $path = 'pembayaran-bukti/bukti.png'): int
    {
        return DB::table('pembayaran')->insertGetId([
            'tagihan_id' => $tagihanId,
            'diajukan_oleh_id' => $this->orangTua->id,
            'path_bukti' => $path,
            'jumlah' => $jumlah,
            'status' => $status,
            'diajukan_pada' => now(),
            'diverifikasi_oleh_id' => null,
            'diverifikasi_pada' => null,
            'catatan' => null,
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

    public function test_admin_dapat_melihat_daftar_bukti_pembayaran_yang_masuk(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000);
        $nomorTagihan = DB::table('tagihan')->where('id', $tagihanId)->value('nomor_tagihan');
        $this->buatPembayaran($tagihanId, 'menunggu', 1500000);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->assertOk()
            ->assertSee('Bukti Pembayaran Masuk')
            ->assertSee($nomorTagihan)
            ->assertSee($this->orangTua->nama)
            ->assertSee('Rp 1.500.000,00')
            ->assertSee('Menunggu')
            ->assertSee('Lihat bukti')
            ->assertSee('Unduh');
    }

    public function test_admin_dapat_memfilter_bukti_pembayaran_berdasarkan_status(): void
    {
        $pendaftaran1 = $this->buatPendaftaran('disetujui', 1500000);
        $tagihan1 = $this->buatTagihan($pendaftaran1, 1500000);
        $nomor1 = DB::table('tagihan')->where('id', $tagihan1)->value('nomor_tagihan');
        $this->buatPembayaran($tagihan1, 'menunggu', 1500000);

        $pendaftaran2 = $this->buatPendaftaran('disetujui', 2000000);
        $tagihan2 = $this->buatTagihan($pendaftaran2, 2000000);
        $nomor2 = DB::table('tagihan')->where('id', $tagihan2)->value('nomor_tagihan');
        $this->buatPembayaran($tagihan2, 'terverifikasi', 2000000);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->assertViewHas('daftarPembayaran', fn ($list) => $list->count() === 2)
            ->set('filterPembayaran', 'menunggu')
            ->assertViewHas('daftarPembayaran', fn ($list) => $list->count() === 1 && $list->first()->nomor_tagihan === $nomor1)
            ->set('filterPembayaran', 'terverifikasi')
            ->assertViewHas('daftarPembayaran', fn ($list) => $list->count() === 1 && $list->first()->nomor_tagihan === $nomor2);
    }

    public function test_admin_dapat_melihat_detail_dan_pratinjau_bukti_pembayaran_gambar(): void
    {
        Storage::fake('local');
        $path = 'pembayaran-bukti/transfer.png';
        Storage::disk('local')->put($path, 'konten gambar tiruan');

        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000);
        $nomorTagihan = DB::table('tagihan')->where('id', $tagihanId)->value('nomor_tagihan');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', 1500000, $path);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('lihatBukti', $pembayaranId)
            ->assertSet('viewingPembayaranId', $pembayaranId)
            ->assertSee('Bukti Pembayaran Masuk')
            ->assertSee($nomorTagihan)
            ->assertSee($this->orangTua->nama)
            ->assertSee('Nominal Tagihan:')
            ->assertSee('Nominal Bukti Pembayaran:')
            ->assertSee('Unduh berkas bukti');
    }

    public function test_admin_dapat_melihat_detail_bukti_pembayaran_pdf(): void
    {
        Storage::fake('local');
        $path = 'pembayaran-bukti/dokumen.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 konten pdf');

        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000);
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', 1500000, $path);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('lihatBukti', $pembayaranId)
            ->assertSet('viewingPembayaranId', $pembayaranId)
            ->assertSee('Dokumen Bukti PDF')
            ->assertSee('Unduh dokumen');
    }

    public function test_admin_dapat_mengunduh_berkas_bukti_pembayaran(): void
    {
        Storage::fake('local');
        $path = 'pembayaran-bukti/transfer.png';
        Storage::disk('local')->put($path, 'konten berkas');

        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000);
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', 1500000, $path);

        $response = Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('unduhBukti', $pembayaranId);

        $response->assertFileDownloaded();
    }

    public function test_admin_dapat_menutup_modal_lihat_bukti(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000);
        $pembayaranId = $this->buatPembayaran($tagihanId);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('lihatBukti', $pembayaranId)
            ->assertSet('viewingPembayaranId', $pembayaranId)
            ->call('tutupLihatBukti')
            ->assertSet('viewingPembayaranId', null);
    }

    public function test_melihat_atau_mengunduh_bukti_pembayaran_yang_tidak_ada_menghasilkan_404(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('lihatBukti', 99999)
            ->assertStatus(404);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('unduhBukti', 99999)
            ->assertStatus(404);
    }

    public function test_pengguna_tanpa_permission_tidak_dapat_melihat_atau_mengunduh_bukti(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000);
        $this->buatPembayaran($tagihanId);

        $this->actingAs($this->orangTua)
            ->get(route('admin.tagihan.index'))
            ->assertForbidden();

        $this->actingAs($this->pengasuh)
            ->get(route('admin.tagihan.index'))
            ->assertForbidden();
    }

    public function test_admin_dapat_memverifikasi_pembayaran_dan_status_tagihan_otomatis_menjadi_lunas(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000, 'belum_lunas');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', 1500000);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiVerifikasi', $pembayaranId)
            ->assertSet('verifyingPembayaranId', $pembayaranId)
            ->assertSee('Verifikasi pembayaran?')
            ->set('verifyingCatatan', 'Pembayaran klir')
            ->call('verifikasiPembayaran')
            ->assertHasNoErrors()
            ->assertSee('Pembayaran berhasil diverifikasi. Status tagihan otomatis menjadi Lunas.');

        $this->assertDatabaseHas('pembayaran', [
            'id' => $pembayaranId,
            'status' => 'terverifikasi',
            'diverifikasi_oleh_id' => $this->admin->id,
            'catatan' => 'Pembayaran klir',
        ]);
        $this->assertNotNull(DB::table('pembayaran')->where('id', $pembayaranId)->value('diverifikasi_pada'));

        $this->assertDatabaseHas('tagihan', [
            'id' => $tagihanId,
            'status' => 'lunas',
        ]);
        $this->assertNotNull(DB::table('tagihan')->where('id', $tagihanId)->value('lunas_pada'));
    }

    public function test_admin_dapat_membatalkan_verifikasi_pembayaran(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000, 'belum_lunas');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', 1500000);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiVerifikasi', $pembayaranId)
            ->call('batalVerifikasi')
            ->assertSet('verifyingPembayaranId', null)
            ->assertSet('verifyingNomorTagihan', '');

        $this->assertDatabaseHas('pembayaran', ['id' => $pembayaranId, 'status' => 'menunggu']);
        $this->assertDatabaseHas('tagihan', ['id' => $tagihanId, 'status' => 'belum_lunas']);
    }

    public function test_admin_dapat_menolak_pembayaran_dengan_alasan(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000, 'belum_lunas');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', 1500000);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiTolak', $pembayaranId)
            ->assertSet('rejectingPembayaranId', $pembayaranId)
            ->assertSee('Tolak bukti pembayaran?')
            ->set('rejectingCatatan', 'Nominal transfer kurang Rp 50.000')
            ->call('tolakPembayaran')
            ->assertHasNoErrors()
            ->assertSee('Pembayaran ditolak. Orang tua dapat mengunggah bukti pembayaran baru.');

        $this->assertDatabaseHas('pembayaran', [
            'id' => $pembayaranId,
            'status' => 'ditolak',
            'diverifikasi_oleh_id' => $this->admin->id,
            'catatan' => 'Nominal transfer kurang Rp 50.000',
        ]);
        $this->assertDatabaseHas('tagihan', [
            'id' => $tagihanId,
            'status' => 'belum_lunas',
            'lunas_pada' => null,
        ]);
    }

    public function test_alasan_penolakan_pembayaran_wajib_diisi(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000, 'belum_lunas');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', 1500000);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiTolak', $pembayaranId)
            ->set('rejectingCatatan', '')
            ->call('tolakPembayaran')
            ->assertHasErrors(['rejectingCatatan']);

        $this->assertDatabaseHas('pembayaran', ['id' => $pembayaranId, 'status' => 'menunggu']);
    }

    public function test_admin_dapat_membatalkan_penolakan_pembayaran(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000, 'belum_lunas');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', 1500000);

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiTolak', $pembayaranId)
            ->call('batalTolak')
            ->assertSet('rejectingPembayaranId', null)
            ->assertSet('rejectingNomorTagihan', '');

        $this->assertDatabaseHas('pembayaran', ['id' => $pembayaranId, 'status' => 'menunggu']);
    }

    public function test_pembayaran_yang_sudah_terverifikasi_atau_ditolak_tidak_dapat_diproses_lagi(): void
    {
        $pendaftaranId = $this->buatPendaftaran('disetujui', 1500000);
        $tagihanId = $this->buatTagihan($pendaftaranId, 1500000, 'belum_lunas');

        foreach (['terverifikasi', 'ditolak'] as $status) {
            $pembayaranId = $this->buatPembayaran($tagihanId, $status, 1500000);

            Livewire::actingAs($this->admin)
                ->test(TagihanIndex::class)
                ->call('bukaKonfirmasiVerifikasi', $pembayaranId)
                ->assertStatus(404);

            Livewire::actingAs($this->admin)
                ->test(TagihanIndex::class)
                ->call('bukaKonfirmasiTolak', $pembayaranId)
                ->assertStatus(404);
        }
    }

    public function test_admin_dapat_mencari_tagihan_berdasarkan_nomor_atau_nama_orang_tua(): void
    {
        $ortu1 = User::factory()->create(['nama' => 'Budi Santoso'])->assignRole('orang_tua');
        $ortu2 = User::factory()->create(['nama' => 'Siti Rahma'])->assignRole('orang_tua');

        $paket1 = PaketLayanan::create(['nama' => 'Paket A', 'harga' => 1500000, 'aktif' => true]);
        $pend1 = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $ortu1->id,
            'paket_layanan_id' => $paket1->id,
            'status' => 'disetujui',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tagihan1 = $this->buatTagihan($pend1, 1500000, 'belum_lunas');
        $nomor1 = DB::table('tagihan')->where('id', $tagihan1)->value('nomor_tagihan');

        $paket2 = PaketLayanan::create(['nama' => 'Paket B', 'harga' => 2000000, 'aktif' => true]);
        $pend2 = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $ortu2->id,
            'paket_layanan_id' => $paket2->id,
            'status' => 'disetujui',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tagihan2 = $this->buatTagihan($pend2, 2000000, 'belum_lunas');
        $nomor2 = DB::table('tagihan')->where('id', $tagihan2)->value('nomor_tagihan');

        Livewire::actingAs($this->admin)
            ->test(TagihanIndex::class)
            ->set('cariTagihan', 'Budi')
            ->assertViewHas('tagihan', fn ($list) => $list->contains('id', $tagihan1) && ! $list->contains('id', $tagihan2))
            ->set('cariTagihan', $nomor2)
            ->assertViewHas('tagihan', fn ($list) => $list->contains('id', $tagihan2) && ! $list->contains('id', $tagihan1))
            ->set('cariTagihan', '')
            ->assertViewHas('tagihan', fn ($list) => $list->contains('id', $tagihan1) && $list->contains('id', $tagihan2));
    }
}
