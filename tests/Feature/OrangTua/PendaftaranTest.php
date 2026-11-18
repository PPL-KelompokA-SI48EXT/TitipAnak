<?php

namespace Tests\Feature\OrangTua;

use App\Livewire\OrangTua\PendaftaranBuat;
use App\Livewire\OrangTua\PendaftaranDetail;
use App\Livewire\OrangTua\PendaftaranIndex;
use App\Livewire\OrangTua\PenjemputIndex;
use App\Models\Anak;
use App\Models\CatatanKesehatan;
use App\Models\PaketLayanan;
use App\Models\Pendaftaran;
use App\Models\PenjemputTerdaftar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    // ---------------------------------------------------------------
    // PBI-11b: Orang tua melihat status pendaftaran (tahap saat ini)
    // ---------------------------------------------------------------

    private function pendaftaran(string $status, array $atribut = []): Pendaftaran
    {
        return Pendaftaran::create([
            'orang_tua_id' => $this->orangTua->id,
            'paket_layanan_id' => $this->paket->id,
            'status' => $status,
            ...$atribut,
        ]);
    }

    public function test_daftar_menampilkan_tahap_dan_tautan_status(): void
    {
        $p = $this->pendaftaran('menunggu_verifikasi', ['diajukan_pada' => now()]);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.pendaftaran.index'))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Tahap 2 dari 3')
            ->assertSee(route('orang-tua.pendaftaran.detail', $p));
    }

    public function test_orang_tua_dapat_membuka_halaman_status_pendaftaran(): void
    {
        $p = $this->pendaftaran('draft');

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.pendaftaran.detail', $p))
            ->assertOk()
            ->assertSee('Status Pendaftaran')
            ->assertSee('Full Day')
            ->assertSeeInOrder(['Draft', 'Menunggu Verifikasi', 'Disetujui'])
            ->assertSee('Masih Draft.');
    }

    public function test_status_draft_berada_di_tahap_pertama(): void
    {
        $tahapan = $this->pendaftaran('draft')->tahapan();

        $this->assertSame(['aktif', 'belum', 'belum'], array_column($tahapan, 'keadaan'));
        $this->assertNotNull($tahapan[0]['waktu']);
        $this->assertNull($tahapan[1]['waktu']);
    }

    public function test_status_menunggu_verifikasi_berada_di_tahap_kedua(): void
    {
        $diajukan = now()->subDay();
        $p = $this->pendaftaran('menunggu_verifikasi', ['diajukan_pada' => $diajukan]);
        $tahapan = $p->tahapan();

        $this->assertSame(['selesai', 'aktif', 'belum'], array_column($tahapan, 'keadaan'));
        $this->assertSame($diajukan->format('Y-m-d H:i'), $tahapan[1]['waktu']->format('Y-m-d H:i'));
        $this->assertNull($tahapan[2]['waktu']);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $p->id])
            ->assertSee('sedang diverifikasi Admin');
    }

    public function test_status_disetujui_menyelesaikan_semua_tahap(): void
    {
        $p = $this->pendaftaran('disetujui', [
            'diajukan_pada' => now()->subDays(2),
            'diverifikasi_oleh_id' => $this->admin->id,
            'diverifikasi_pada' => now()->subDay(),
        ]);

        $this->assertSame(['selesai', 'selesai', 'selesai'], array_column($p->tahapan(), 'keadaan'));

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $p->id])
            ->assertSee('Disetujui.')
            ->assertSee('sudah diterima Admin');
    }

    public function test_status_ditolak_menampilkan_alasan_penolakan(): void
    {
        $p = $this->pendaftaran('ditolak', [
            'diajukan_pada' => now()->subDays(2),
            'diverifikasi_oleh_id' => $this->admin->id,
            'diverifikasi_pada' => now()->subDay(),
            'catatan_penolakan' => 'Usia anak tidak sesuai dengan paket yang dipilih.',
        ]);

        $tahapan = $p->tahapan();
        $this->assertSame('ditolak', $tahapan[2]['status']);
        $this->assertSame('selesai', $tahapan[2]['keadaan']);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $p->id])
            ->assertSee('Ditolak.')
            ->assertSee('Usia anak tidak sesuai dengan paket yang dipilih.');
    }

    public function test_halaman_status_menampilkan_nama_anak_jika_sudah_diisi(): void
    {
        $p = $this->pendaftaran('menunggu_verifikasi', ['diajukan_pada' => now()]);
        Anak::create([
            'pendaftaran_id' => $p->id,
            'orang_tua_id' => $this->orangTua->id,
            'nama_lengkap' => 'Zidan Alfarizi',
            'tanggal_lahir' => today()->subMonths(20),
            'jenis_kelamin' => 'L',
        ]);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.pendaftaran.detail', $p))
            ->assertOk()
            ->assertSee('Zidan Alfarizi');
    }

    public function test_orang_tua_tidak_dapat_melihat_status_pendaftaran_orang_lain(): void
    {
        $lainnya = User::factory()->create()->assignRole('orang_tua');
        $milikLain = Pendaftaran::create(['orang_tua_id' => $lainnya->id, 'paket_layanan_id' => $this->paket->id, 'status' => 'draft']);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.pendaftaran.detail', $milikLain))
            ->assertNotFound();
    }

    public function test_admin_tidak_boleh_membuka_halaman_status_orang_tua(): void
    {
        $p = $this->pendaftaran('draft');

        $this->actingAs($this->admin)
            ->get(route('orang-tua.pendaftaran.detail', $p))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $p = $this->pendaftaran('draft');

        $this->get(route('orang-tua.pendaftaran.detail', $p))
            ->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // PBI-11c: Orang tua menghapus pendaftaran yang masih Draft
    // ---------------------------------------------------------------

    private function anakUntuk(Pendaftaran $p, string $nama = 'Naufal Hakim Santoso'): Anak
    {
        return Anak::create([
            'pendaftaran_id' => $p->id,
            'orang_tua_id' => $p->orang_tua_id,
            'nama_lengkap' => $nama,
            'tanggal_lahir' => today()->subMonths(34),
            'jenis_kelamin' => 'L',
        ]);
    }

    public function test_tombol_hapus_hanya_tampil_untuk_pendaftaran_draft(): void
    {
        $draft = $this->pendaftaran('draft');
        $menunggu = $this->pendaftaran('menunggu_verifikasi', ['diajukan_pada' => now()]);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranIndex::class)
            ->assertSeeHtml('wire:click="konfirmasiHapus('.$draft->id.')"')
            ->assertDontSeeHtml('wire:click="konfirmasiHapus('.$menunggu->id.')"');
    }

    public function test_dialog_konfirmasi_menampilkan_paket_dan_nama_anak(): void
    {
        $p = $this->pendaftaran('draft');
        $this->anakUntuk($p);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranIndex::class)
            ->assertDontSee('Hapus pendaftaran?')
            ->call('konfirmasiHapus', $p->id)
            ->assertSet('hapusId', $p->id)
            ->assertSee('Hapus pendaftaran?')
            ->assertSee('Naufal Hakim Santoso')
            ->assertSee('Ya, hapus');
    }

    public function test_batal_menutup_dialog_tanpa_menghapus(): void
    {
        $p = $this->pendaftaran('draft');

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranIndex::class)
            ->call('konfirmasiHapus', $p->id)
            ->call('batalHapus')
            ->assertSet('hapusId', null)
            ->assertDontSee('Hapus pendaftaran?');

        $this->assertNotSoftDeleted($p);
    }

    public function test_orang_tua_menghapus_pendaftaran_draft_beserta_data_anak(): void
    {
        $p = $this->pendaftaran('draft');
        $anak = $this->anakUntuk($p);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranIndex::class)
            ->call('konfirmasiHapus', $p->id)
            ->call('hapus')
            ->assertSet('hapusId', null)
            ->assertSee('Pendaftaran Draft berhasil dihapus.')
            ->assertSee('Belum ada pendaftaran.');

        $this->assertSoftDeleted($p);
        $this->assertSoftDeleted($anak);
    }

    public function test_pendaftaran_selain_draft_tidak_dapat_dihapus(): void
    {
        foreach (['menunggu_verifikasi', 'disetujui', 'ditolak'] as $status) {
            $p = $this->pendaftaran($status, ['diajukan_pada' => now()]);

            Livewire::actingAs($this->orangTua)
                ->test(PendaftaranIndex::class)
                ->call('konfirmasiHapus', $p->id)
                ->assertNotFound();

            $this->assertNotSoftDeleted($p);
        }
    }

    public function test_draft_yang_sudah_diajukan_saat_dialog_terbuka_tidak_terhapus(): void
    {
        $p = $this->pendaftaran('draft');

        $komponen = Livewire::actingAs($this->orangTua)
            ->test(PendaftaranIndex::class)
            ->call('konfirmasiHapus', $p->id);

        $p->update(['status' => 'menunggu_verifikasi', 'diajukan_pada' => now()]);

        $komponen->call('hapus')->assertNotFound();

        $this->assertNotSoftDeleted($p);
    }

    public function test_orang_tua_tidak_dapat_menghapus_pendaftaran_orang_lain(): void
    {
        $lainnya = User::factory()->create()->assignRole('orang_tua');
        $milikLain = Pendaftaran::create(['orang_tua_id' => $lainnya->id, 'paket_layanan_id' => $this->paket->id, 'status' => 'draft']);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranIndex::class)
            ->call('konfirmasiHapus', $milikLain->id)
            ->assertNotFound();

        $this->assertNotSoftDeleted($milikLain);
    }

    public function test_hapus_dari_halaman_status_mengarahkan_ke_daftar(): void
    {
        $p = $this->pendaftaran('draft');

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $p->id])
            ->assertSee('Hapus pendaftaran')
            ->call('konfirmasiHapus')
            ->assertSee('Hapus pendaftaran?')
            ->call('hapus')
            ->assertRedirect(route('orang-tua.pendaftaran.index'));

        $this->assertSoftDeleted($p);
    }

    public function test_halaman_status_selain_draft_tidak_menampilkan_tombol_hapus(): void
    {
        $p = $this->pendaftaran('menunggu_verifikasi', ['diajukan_pada' => now()]);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $p->id])
            ->assertDontSee('Hapus pendaftaran')
            ->call('hapus')
            ->assertNotFound();

        $this->assertNotSoftDeleted($p);
    }

    public function test_pendaftaran_terhapus_tidak_bisa_dibuka_lagi(): void
    {
        $p = $this->pendaftaran('draft');
        $p->hapusDraft();

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.pendaftaran.detail', $p))
            ->assertNotFound();
    }

    // ---------------------------------------------------------------
    // PBI-11d: Draft otomatis menjadi Menunggu Verifikasi saat data anak,
    // catatan kesehatan, dan penjemput terdaftar sudah lengkap
    // ---------------------------------------------------------------

    private function kesehatanUntuk(Anak $anak): CatatanKesehatan
    {
        return CatatanKesehatan::create(['anak_id' => $anak->id, 'alergi' => 'Susu sapi']);
    }

    private function penjemputUntuk(Anak $anak, bool $aktif = true): PenjemputTerdaftar
    {
        return PenjemputTerdaftar::create([
            'anak_id' => $anak->id, 'nama_lengkap' => 'Budi Santoso', 'hubungan' => 'Ayah',
            'no_hp' => '081234567890', 'aktif' => $aktif,
        ]);
    }

    public function test_draft_tetap_draft_jika_baru_data_anak_yang_diisi(): void
    {
        $p = $this->pendaftaran('draft');
        $this->anakUntuk($p);

        $this->assertSame('draft', $p->fresh()->status);
        $this->assertNull($p->fresh()->diajukan_pada);
    }

    public function test_draft_tetap_draft_jika_penjemput_belum_ada(): void
    {
        $p = $this->pendaftaran('draft');
        $this->kesehatanUntuk($this->anakUntuk($p));

        $this->assertSame('draft', $p->fresh()->status);
    }

    public function test_draft_tetap_draft_jika_catatan_kesehatan_belum_ada(): void
    {
        $p = $this->pendaftaran('draft');
        $this->penjemputUntuk($this->anakUntuk($p));

        $this->assertSame('draft', $p->fresh()->status);
    }

    public function test_penjemput_nonaktif_tidak_dihitung_lengkap(): void
    {
        $p = $this->pendaftaran('draft');
        $anak = $this->anakUntuk($p);
        $this->kesehatanUntuk($anak);
        $this->penjemputUntuk($anak, aktif: false);

        $this->assertSame('draft', $p->fresh()->status);
        $this->assertFalse($p->kelengkapan()['penjemput']['lengkap']);
    }

    public function test_status_otomatis_menunggu_verifikasi_saat_semua_data_lengkap(): void
    {
        $this->travelTo(now()->setTime(9, 30));
        $p = $this->pendaftaran('draft');
        $anak = $this->anakUntuk($p);
        $this->kesehatanUntuk($anak);

        $this->penjemputUntuk($anak);

        $p->refresh();
        $this->assertSame('menunggu_verifikasi', $p->status);
        $this->assertTrue($p->diajukan_pada->equalTo(now()));
        $this->assertNull($p->diverifikasi_pada);
    }

    public function test_urutan_pengisian_tidak_berpengaruh(): void
    {
        $p = $this->pendaftaran('draft');
        $anak = $this->anakUntuk($p);
        $this->penjemputUntuk($anak);
        $this->assertSame('draft', $p->fresh()->status);

        // Catatan kesehatan diisi paling akhir.
        $this->kesehatanUntuk($anak);

        $this->assertSame('menunggu_verifikasi', $p->fresh()->status);
    }

    public function test_penjemput_yang_diaktifkan_kembali_melengkapi_pendaftaran(): void
    {
        $p = $this->pendaftaran('draft');
        $anak = $this->anakUntuk($p);
        $this->kesehatanUntuk($anak);
        $penjemput = $this->penjemputUntuk($anak, aktif: false);

        $penjemput->update(['aktif' => true]);

        $this->assertSame('menunggu_verifikasi', $p->fresh()->status);
    }

    public function test_menambah_penjemput_dari_halaman_penjemput_mengajukan_pendaftaran(): void
    {
        Storage::fake('local');
        $p = $this->pendaftaran('draft');
        $anak = $this->anakUntuk($p);
        $this->kesehatanUntuk($anak);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->set('anak_id', (string) $anak->id)
            ->set('nama_lengkap', 'Rina Santoso')
            ->set('hubungan', 'Ibu')
            ->set('no_hp', '081298765432')
            ->set('foto_identitas', UploadedFile::fake()->create('ktp.png', 100, 'image/png'))
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame('menunggu_verifikasi', $p->fresh()->status);
    }

    public function test_status_selain_draft_tidak_diubah(): void
    {
        foreach (['menunggu_verifikasi', 'disetujui', 'ditolak'] as $status) {
            $diajukan = now()->subDays(3)->startOfSecond();
            $p = $this->pendaftaran($status, ['diajukan_pada' => $diajukan]);
            $anak = $this->anakUntuk($p);
            $this->kesehatanUntuk($anak);
            $this->penjemputUntuk($anak);

            $p->refresh();
            $this->assertSame($status, $p->status);
            $this->assertTrue($p->diajukan_pada->equalTo($diajukan), "diajukan_pada {$status} tidak boleh berubah");
        }
    }

    public function test_draft_yang_sudah_dihapus_tidak_diajukan(): void
    {
        $p = $this->pendaftaran('draft');
        $anak = $this->anakUntuk($p);
        $this->kesehatanUntuk($anak);
        $p->hapusDraft();

        $this->assertFalse($p->ajukanJikaLengkap());
        $this->assertSame('draft', Pendaftaran::withTrashed()->find($p->id)->status);
    }

    public function test_halaman_status_draft_menampilkan_kelengkapan_data(): void
    {
        $p = $this->pendaftaran('draft');
        $this->kesehatanUntuk($this->anakUntuk($p));

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $p->id])
            ->assertSee('Kelengkapan data')
            ->assertSee('2 dari 3 lengkap')
            ->assertSeeInOrder(['Data anak', 'Lengkap', 'Catatan kesehatan', 'Lengkap', 'Penjemput terdaftar', 'Belum diisi'])
            ->assertSee('otomatis berubah menjadi Menunggu Verifikasi')
            ->assertSeeHtml('href="'.route('orang-tua.penjemput.index').'"');
    }

    public function test_halaman_status_draft_tanpa_anak_tidak_menawarkan_tambah_penjemput(): void
    {
        $p = $this->pendaftaran('draft');

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $p->id])
            ->assertSee('0 dari 3 lengkap')
            ->assertDontSee('Tambah penjemput');
    }

    public function test_halaman_status_setelah_lengkap_menampilkan_menunggu_verifikasi(): void
    {
        $p = $this->pendaftaran('draft');
        $anak = $this->anakUntuk($p);
        $this->kesehatanUntuk($anak);
        $this->penjemputUntuk($anak);

        Livewire::actingAs($this->orangTua)
            ->test(PendaftaranDetail::class, ['pendaftaran' => $p->id])
            ->assertSee('Pendaftaran Anda sedang diverifikasi Admin.')
            ->assertSee('Data lengkap dan diajukan ke Admin.')
            ->assertDontSee('Kelengkapan data')
            ->assertDontSee('Hapus pendaftaran');
    }
}
