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
