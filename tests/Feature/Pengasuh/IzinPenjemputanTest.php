<?php

namespace Tests\Feature\Pengasuh;

use App\Livewire\Pengasuh\IzinPenjemputanIndex;
use App\Models\IzinPenjemputan;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class IzinPenjemputanTest extends TestCase
{
    use RefreshDatabase;

    private User $orangTua;

    private User $pengasuh;

    private int $anakId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');

        $paket = PaketLayanan::create(['nama' => 'Paket Uji', 'harga' => 100000, 'aktif' => true]);
        $pendaftaranId = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $this->orangTua->id, 'paket_layanan_id' => $paket->id, 'status' => 'disetujui',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->anakId = DB::table('anak')->insertGetId([
            'pendaftaran_id' => $pendaftaranId, 'orang_tua_id' => $this->orangTua->id, 'nama_lengkap' => 'Nadia Pratama',
            'tanggal_lahir' => '2022-01-01', 'jenis_kelamin' => 'P', 'status' => 'aktif',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function buatIzin(string $status = 'menunggu', ?string $tanggal = null, string $path = 'izin-penjemputan/ktp.png'): IzinPenjemputan
    {
        return IzinPenjemputan::create([
            'anak_id' => $this->anakId, 'diajukan_oleh_id' => $this->orangTua->id, 'nama_penjemput' => 'Paman Heru',
            'path_foto_identitas' => $path, 'alasan' => 'Orang tua lembur.',
            'tanggal_jemput' => $tanggal ?? today()->toDateString(), 'status' => $status,
        ]);
    }

    public function test_halaman_hanya_untuk_pengasuh(): void
    {
        $this->actingAs($this->pengasuh)->get(route('pengasuh.izin-penjemputan.index'))->assertOk()->assertSee('Izin Penjemputan');
        $this->actingAs($this->orangTua)->get(route('pengasuh.izin-penjemputan.index'))->assertForbidden();
    }

    public function test_daftar_menampilkan_izin_menunggu_secara_default_dan_bisa_difilter(): void
    {
        $this->buatIzin('menunggu');
        $this->buatIzin('ditolak');

        Livewire::actingAs($this->pengasuh)->test(IzinPenjemputanIndex::class)
            ->assertSee('Setujui')
            ->assertSet('filterStatus', 'menunggu')
            ->set('filterStatus', 'ditolak')
            ->assertDontSee('Setujui')
            ->set('filterStatus', 'semua')
            ->assertSee('Setujui');
    }

    public function test_pengasuh_menyetujui_izin_dan_tercatat_siapa_yang_memvalidasi(): void
    {
        $izin = $this->buatIzin();

        Livewire::actingAs($this->pengasuh)->test(IzinPenjemputanIndex::class)
            ->call('setujui', $izin->id)->assertSee('disetujui');

        $izin->refresh();
        $this->assertSame('disetujui', $izin->status);
        $this->assertSame($this->pengasuh->id, $izin->divalidasi_oleh_id);
        $this->assertNotNull($izin->divalidasi_pada);
    }

    public function test_pengasuh_menolak_izin(): void
    {
        $izin = $this->buatIzin();

        Livewire::actingAs($this->pengasuh)->test(IzinPenjemputanIndex::class)
            ->call('tolak', $izin->id)->assertSee('ditolak');

        $this->assertSame('ditolak', $izin->fresh()->status);
        $this->assertSame($this->pengasuh->id, $izin->fresh()->divalidasi_oleh_id);
    }

    public function test_izin_yang_sudah_dibatalkan_orang_tua_tidak_bisa_disetujui(): void
    {
        $izin = $this->buatIzin('dibatalkan');

        Livewire::actingAs($this->pengasuh)->test(IzinPenjemputanIndex::class)
            ->call('setujui', $izin->id)->assertSee('sudah berstatus dibatalkan');

        $this->assertSame('dibatalkan', $izin->fresh()->status);
        $this->assertNull($izin->fresh()->divalidasi_oleh_id);
    }

    public function test_izin_dengan_tanggal_lewat_tidak_bisa_divalidasi(): void
    {
        $izin = $this->buatIzin('menunggu', today()->subDay()->toDateString());

        Livewire::actingAs($this->pengasuh)->test(IzinPenjemputanIndex::class)
            ->call('setujui', $izin->id)->assertSee('sudah lewat');

        $this->assertSame('menunggu', $izin->fresh()->status);
    }

    public function test_pengasuh_dapat_melihat_foto_ktp_lokal_maupun_url_contoh(): void
    {
        Storage::disk('local')->put('izin-penjemputan/ktp.png', 'isi-foto');
        $lokal = $this->buatIzin();
        $contoh = $this->buatIzin('menunggu', null, 'https://contoh.test/ktp.png');

        Livewire::actingAs($this->pengasuh)->test(IzinPenjemputanIndex::class)
            ->call('lihatFoto', $lokal->id)->assertSet('previewFotoUrl', 'data:'.Storage::disk('local')->mimeType('izin-penjemputan/ktp.png').';base64,'.base64_encode('isi-foto'))
            ->call('lihatFoto', $contoh->id)->assertSet('previewFotoUrl', 'https://contoh.test/ktp.png')
            ->call('tutupFoto')->assertSet('viewingFotoId', null);
    }

    public function test_orang_tua_tidak_bisa_memvalidasi(): void
    {
        $izin = $this->buatIzin();

        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)->assertForbidden();
        $this->assertSame('menunggu', $izin->fresh()->status);
    }

    public function test_izin_yang_disetujui_dapat_dipakai_check_out(): void
    {
        $izin = $this->buatIzin();

        Livewire::actingAs($this->pengasuh)->test(IzinPenjemputanIndex::class)->call('setujui', $izin->id);

        $this->assertTrue(IzinPenjemputan::query()->disetujui()->padaTanggal(today())->whereKey($izin->id)->exists());
    }
}
