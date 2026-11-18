<?php

namespace Tests\Feature\OrangTua;

use App\Livewire\OrangTua\TagihanIndex;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TagihanIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $orangTua;

    private User $orangTuaLain;

    private User $pengasuh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
        $this->orangTuaLain = User::factory()->create()->assignRole('orang_tua');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
    }

    private function buatTagihan(User $orangTua, string $status = 'belum_lunas', int $harga = 1500000, ?string $nomor = null): int
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket '.fake()->unique()->numberBetween(1000, 9999),
            'harga' => $harga,
            'aktif' => true,
        ]);

        $pendaftaranId = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $orangTua->id,
            'paket_layanan_id' => $paket->id,
            'status' => 'disetujui',
            'diajukan_pada' => now(),
            'diverifikasi_oleh_id' => null,
            'diverifikasi_pada' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('tagihan')->insertGetId([
            'pendaftaran_id' => $pendaftaranId,
            'nomor_tagihan' => $nomor ?? 'TGH-'.now()->format('Ym').'-'.fake()->unique()->numberBetween(1000, 9999),
            'jumlah' => $harga,
            'status' => $status,
            'diterbitkan_pada' => now()->subDay(),
            'jatuh_tempo' => today()->addDays(9),
            'lunas_pada' => $status === 'lunas' ? now() : null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
    }

    private function buatPembayaran(int $tagihanId, string $status = 'menunggu', string $path = 'pembayaran-bukti/lama.png'): int
    {
        return DB::table('pembayaran')->insertGetId([
            'tagihan_id' => $tagihanId,
            'diajukan_oleh_id' => $this->orangTua->id,
            'path_bukti' => $path,
            'jumlah' => 1500000,
            'status' => $status,
            'diajukan_pada' => now()->subHour(),
            'diverifikasi_oleh_id' => null,
            'diverifikasi_pada' => null,
            'catatan' => null,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
    }

    public function test_orang_tua_dapat_melihat_rincian_dan_status_tagihan_miliknya(): void
    {
        $this->buatTagihan($this->orangTua, 'belum_lunas', 1500000, 'TGH-OWN-0001');
        $this->buatTagihan($this->orangTua, 'lunas', 2750000, 'TGH-OWN-0002');

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.tagihan.index'))
            ->assertOk()
            ->assertSee('Rincian Tagihan')
            ->assertSee('TGH-OWN-0001')
            ->assertSee('TGH-OWN-0002')
            ->assertSee('Rp 1.500.000,00')
            ->assertSee('Rp 2.750.000,00')
            ->assertSee('Belum Lunas')
            ->assertSee('Lunas')
            ->assertSee('Jatuh tempo');
    }

    public function test_orang_tua_hanya_melihat_tagihan_miliknya(): void
    {
        $this->buatTagihan($this->orangTua, 'belum_lunas', 1500000, 'TGH-MILIK-0001');
        $this->buatTagihan($this->orangTuaLain, 'belum_lunas', 2750000, 'TGH-ORANG-0002');

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.tagihan.index'))
            ->assertOk()
            ->assertSee('TGH-MILIK-0001')
            ->assertDontSee('TGH-ORANG-0002');
    }

    public function test_tagihan_soft_deleted_tidak_tampil_di_daftar_orang_tua(): void
    {
        $tagihanId = $this->buatTagihan($this->orangTua, 'belum_lunas', 1500000, 'TGH-AKTIF-0001');
        DB::table('tagihan')->where('id', $tagihanId)->update(['deleted_at' => now()]);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.tagihan.index'))
            ->assertOk()
            ->assertSee('Belum ada tagihan')
            ->assertDontSee('TGH-AKTIF-0001');
    }

    public function test_orang_tua_dapat_mengunggah_bukti_jpg_png_dan_pdf(): void
    {
        Storage::fake('local');

        $files = [
            ['jpg', UploadedFile::fake()->image('bukti.jpg'), 'image/jpeg'],
            ['png', UploadedFile::fake()->image('bukti.png'), 'image/png'],
            ['pdf', UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'), 'application/pdf'],
        ];

        foreach ($files as [$format, $file, $mime]) {
            $tagihanId = $this->buatTagihan($this->orangTua, 'belum_lunas', 1500000, 'TGH-UPLOAD-'.$format);

            Livewire::actingAs($this->orangTua)
                ->test(TagihanIndex::class)
                ->call('bukaFormUpload', $tagihanId)
                ->set('buktiPembayaran', $file)
                ->call('unggahBukti')
                ->assertHasNoErrors()
                ->assertSee('Bukti pembayaran berhasil diunggah.');

            $pembayaran = DB::table('pembayaran')->where('tagihan_id', $tagihanId)->first();

            $this->assertNotNull($pembayaran);
            $this->assertSame($this->orangTua->id, $pembayaran->diajukan_oleh_id);
            $this->assertSame('menunggu', $pembayaran->status);
            $this->assertEquals(1500000, $pembayaran->jumlah);
            $this->assertSame($mime, Storage::disk('local')->mimeType($pembayaran->path_bukti));
            Storage::disk('local')->assertExists($pembayaran->path_bukti);
        }
    }

    public function test_bukti_pembayaran_harus_jpg_png_atau_pdf_maksimal_2_mb(): void
    {
        Storage::fake('local');
        $tagihanId = $this->buatTagihan($this->orangTua);

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->call('bukaFormUpload', $tagihanId)
            ->set('buktiPembayaran', UploadedFile::fake()->create('bukti.txt', 100, 'text/plain'))
            ->call('unggahBukti')
            ->assertHasErrors('buktiPembayaran')
            ->set('buktiPembayaran', UploadedFile::fake()->create('bukti.pdf', 2049, 'application/pdf'))
            ->call('unggahBukti')
            ->assertHasErrors('buktiPembayaran');

        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_orang_tua_tidak_dapat_mengunggah_bukti_tagihan_milik_orang_lain(): void
    {
        $tagihanId = $this->buatTagihan($this->orangTuaLain);

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->call('bukaFormUpload', $tagihanId)
            ->assertStatus(404);
    }

    public function test_tagihan_dengan_pembayaran_menunggu_atau_terverifikasi_tidak_dapat_diunggah_lagi(): void
    {
        foreach (['menunggu', 'terverifikasi'] as $status) {
            $tagihanId = $this->buatTagihan($this->orangTua);
            $this->buatPembayaran($tagihanId, $status);

            Livewire::actingAs($this->orangTua)
                ->test(TagihanIndex::class)
                ->call('bukaFormUpload', $tagihanId)
                ->assertStatus(404);
        }
    }

    public function test_orang_tua_dapat_mengunggah_ulang_setelah_pembayaran_ditolak(): void
    {
        Storage::fake('local');
        $tagihanId = $this->buatTagihan($this->orangTua);
        $this->buatPembayaran($tagihanId, 'ditolak', 'pembayaran-bukti/ditolak.png');

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->call('bukaFormUpload', $tagihanId)
            ->set('buktiPembayaran', UploadedFile::fake()->image('bukti-baru.png'))
            ->call('unggahBukti')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pembayaran', [
            'tagihan_id' => $tagihanId,
            'status' => 'ditolak',
            'path_bukti' => 'pembayaran-bukti/ditolak.png',
        ]);
        $this->assertDatabaseHas('pembayaran', [
            'tagihan_id' => $tagihanId,
            'status' => 'menunggu',
        ]);
        $this->assertDatabaseCount('pembayaran', 2);
    }

    public function test_tagihan_lunas_tidak_dapat_mengunggah_bukti(): void
    {
        $tagihanId = $this->buatTagihan($this->orangTua, 'lunas');

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->call('bukaFormUpload', $tagihanId)
            ->assertStatus(404);
    }

    public function test_orang_tua_dapat_menghapus_bukti_pembayaran_menunggu(): void
    {
        Storage::fake('local');
        $tagihanId = $this->buatTagihan($this->orangTua);
        $path = 'pembayaran-bukti/salah.png';
        Storage::disk('local')->put($path, 'bukti salah');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', $path);

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->assertSee('Menunggu verifikasi')
            ->assertSee('Hapus bukti')
            ->call('bukaKonfirmasiHapusPembayaran', $pembayaranId)
            ->assertSet('deletingPembayaranId', $pembayaranId)
            ->assertSee('Hapus bukti pembayaran?')
            ->call('hapusPembayaran')
            ->assertHasNoErrors()
            ->assertSee('Bukti pembayaran berhasil dihapus.')
            ->assertSee('Unggah bukti');

        $this->assertDatabaseMissing('pembayaran', ['id' => $pembayaranId]);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('tagihan', ['id' => $tagihanId]);
    }

    public function test_orang_tua_dapat_mengunggah_bukti_baru_setelah_menghapus_bukti_yang_salah(): void
    {
        Storage::fake('local');
        $tagihanId = $this->buatTagihan($this->orangTua);
        $pathSalah = 'pembayaran-bukti/salah.png';
        Storage::disk('local')->put($pathSalah, 'bukti salah');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', $pathSalah);

        $buktiBaru = UploadedFile::fake()->image('bukti-benar.jpg');

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusPembayaran', $pembayaranId)
            ->call('hapusPembayaran')
            ->assertHasNoErrors()
            ->call('bukaFormUpload', $tagihanId)
            ->set('buktiPembayaran', $buktiBaru)
            ->call('unggahBukti')
            ->assertHasNoErrors()
            ->assertSee('Bukti pembayaran berhasil diunggah.')
            ->assertSee('Menunggu verifikasi');

        $this->assertDatabaseMissing('pembayaran', ['id' => $pembayaranId]);
        Storage::disk('local')->assertMissing($pathSalah);

        $pembayaranBaru = DB::table('pembayaran')->where('tagihan_id', $tagihanId)->first();
        $this->assertNotNull($pembayaranBaru);
        $this->assertSame('menunggu', $pembayaranBaru->status);
        Storage::disk('local')->assertExists($pembayaranBaru->path_bukti);
    }

    public function test_orang_tua_dapat_membatalkan_hapus_bukti_pembayaran(): void
    {
        Storage::fake('local');
        $tagihanId = $this->buatTagihan($this->orangTua);
        $path = 'pembayaran-bukti/salah.png';
        Storage::disk('local')->put($path, 'bukti salah');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu', $path);

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusPembayaran', $pembayaranId)
            ->call('batalHapusPembayaran')
            ->assertSet('deletingPembayaranId', null)
            ->assertSee('Menunggu verifikasi');

        $this->assertDatabaseHas('pembayaran', ['id' => $pembayaranId, 'status' => 'menunggu']);
        Storage::disk('local')->assertExists($path);
    }

    public function test_bukti_pembayaran_yang_sudah_diverifikasi_atau_ditolak_tidak_dapat_dihapus(): void
    {
        foreach (['terverifikasi', 'ditolak'] as $status) {
            $tagihanId = $this->buatTagihan($this->orangTua);
            $pembayaranId = $this->buatPembayaran($tagihanId, $status);

            Livewire::actingAs($this->orangTua)
                ->test(TagihanIndex::class)
                ->call('bukaKonfirmasiHapusPembayaran', $pembayaranId)
                ->assertStatus(404);
        }
    }

    public function test_orang_tua_tidak_dapat_menghapus_bukti_milik_orang_tua_lain(): void
    {
        Storage::fake('local');
        $tagihanId = $this->buatTagihan($this->orangTuaLain);
        $pembayaranId = DB::table('pembayaran')->insertGetId([
            'tagihan_id' => $tagihanId,
            'diajukan_oleh_id' => $this->orangTuaLain->id,
            'path_bukti' => 'pembayaran-bukti/orang-lain.png',
            'jumlah' => 1500000,
            'status' => 'menunggu',
            'diajukan_pada' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusPembayaran', $pembayaranId)
            ->assertStatus(404);

        $this->assertDatabaseHas('pembayaran', ['id' => $pembayaranId]);
    }

    public function test_bukti_pembayaran_tagihan_lunas_tidak_dapat_dihapus(): void
    {
        $tagihanId = $this->buatTagihan($this->orangTua, 'lunas');
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu');

        Livewire::actingAs($this->orangTua)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusPembayaran', $pembayaranId)
            ->assertStatus(404);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('orang-tua.tagihan.index'))
            ->assertRedirect(route('login'));
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $this->actingAs($this->pengasuh)
            ->get(route('orang-tua.tagihan.index'))
            ->assertForbidden();
    }

    public function test_pengguna_tanpa_permission_tidak_dapat_menghapus_bukti_pembayaran(): void
    {
        $tagihanId = $this->buatTagihan($this->orangTua);
        $pembayaranId = $this->buatPembayaran($tagihanId, 'menunggu');

        Livewire::actingAs($this->pengasuh)
            ->test(TagihanIndex::class)
            ->call('bukaKonfirmasiHapusPembayaran', $pembayaranId)
            ->assertForbidden();

        Livewire::actingAs($this->pengasuh)
            ->test(TagihanIndex::class)
            ->set('deletingPembayaranId', $pembayaranId)
            ->call('hapusPembayaran')
            ->assertForbidden();

        $this->assertDatabaseHas('pembayaran', ['id' => $pembayaranId]);
    }
}
