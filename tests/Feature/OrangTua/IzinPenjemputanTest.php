<?php

namespace Tests\Feature\OrangTua;

use App\Livewire\OrangTua\IzinPenjemputanIndex;
use App\Models\IzinPenjemputan;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class IzinPenjemputanTest extends TestCase
{
    use RefreshDatabase;

    private User $orangTua;

    private User $orangTuaLain;

    private User $pengasuh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $this->orangTua = User::factory()->create()->assignRole('orang_tua');
        $this->orangTuaLain = User::factory()->create()->assignRole('orang_tua');
        $this->pengasuh = User::factory()->create()->assignRole('pengasuh');
    }

    private function buatAnak(User $orangTua, string $nama = 'Nadia Pratama', string $status = 'aktif'): int
    {
        $paket = PaketLayanan::create(['nama' => 'Paket '.fake()->unique()->numberBetween(1000, 9999), 'harga' => 100000, 'aktif' => true]);
        $pendaftaranId = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $orangTua->id, 'paket_layanan_id' => $paket->id, 'status' => 'disetujui',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('anak')->insertGetId([
            'pendaftaran_id' => $pendaftaranId, 'orang_tua_id' => $orangTua->id, 'nama_lengkap' => $nama,
            'tanggal_lahir' => '2022-01-01', 'jenis_kelamin' => 'P', 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function buatIzin(int $anakId, User $pengaju, string $status = 'menunggu'): IzinPenjemputan
    {
        return IzinPenjemputan::create([
            'anak_id' => $anakId, 'diajukan_oleh_id' => $pengaju->id, 'nama_penjemput' => 'Paman Heru',
            'path_foto_identitas' => 'izin-penjemputan/ktp.png', 'alasan' => 'Orang tua lembur.',
            'tanggal_jemput' => today()->toDateString(), 'status' => $status,
        ]);
    }

    private function buatKehadiranDenganIzin(int $anakId, IzinPenjemputan $izin): void
    {
        DB::table('kehadiran')->insert([
            'anak_id' => $anakId, 'tanggal' => today()->toDateString(), 'check_in_pada' => now(),
            'check_in_oleh_id' => $this->pengasuh->id, 'check_out_pada' => now(), 'check_out_oleh_id' => $this->pengasuh->id,
            'izin_penjemputan_id' => $izin->id, 'nama_penjemput' => 'Paman Heru',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function isi(int $anakId, array $override = []): array
    {
        return $override + [
            'anak_id' => (string) $anakId, 'nama_penjemput' => 'Paman Heru', 'tanggal_jemput' => today()->toDateString(),
            'alasan' => 'Orang tua lembur.', 'foto_identitas' => UploadedFile::fake()->image('ktp.png'),
        ];
    }

    public function test_halaman_hanya_untuk_orang_tua(): void
    {
        $this->actingAs($this->orangTua)->get(route('orang-tua.izin-penjemputan.index'))->assertOk()->assertSee('Izin Penjemputan');
        $this->actingAs($this->pengasuh)->get(route('orang-tua.izin-penjemputan.index'))->assertForbidden();
    }

    public function test_orang_tua_mengajukan_izin_berstatus_menunggu(): void
    {
        $anakId = $this->buatAnak($this->orangTua);

        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
            ->set($this->isi($anakId))
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('berhasil diajukan');

        $izin = IzinPenjemputan::firstOrFail();
        $this->assertSame('menunggu', $izin->status);
        $this->assertSame($this->orangTua->id, $izin->diajukan_oleh_id);
        Storage::disk('local')->assertExists($izin->path_foto_identitas);
    }

    public function test_pengajuan_divalidasi(): void
    {
        $anakId = $this->buatAnak($this->orangTua);

        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
            ->set($this->isi($anakId, ['nama_penjemput' => '', 'alasan' => '', 'foto_identitas' => null, 'tanggal_jemput' => today()->subDay()->toDateString()]))
            ->call('simpan')
            ->assertHasErrors(['nama_penjemput', 'alasan', 'foto_identitas', 'tanggal_jemput']);

        $this->assertDatabaseCount('izin_penjemputan', 0);
    }

    public function test_tidak_bisa_mengajukan_untuk_anak_orang_lain_atau_anak_tidak_aktif(): void
    {
        $anakLain = $this->buatAnak($this->orangTuaLain);
        $anakNonaktif = $this->buatAnak($this->orangTua, 'Nonaktif', 'nonaktif');

        foreach ([$anakLain, $anakNonaktif] as $id) {
            Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
                ->set($this->isi($id))->call('simpan')->assertHasErrors(['anak_id']);
        }
        $this->assertDatabaseCount('izin_penjemputan', 0);
    }

    public function test_hanya_menampilkan_izin_milik_sendiri(): void
    {
        $this->buatIzin($this->buatAnak($this->orangTua, 'Anak Saya'), $this->orangTua);
        $this->buatIzin($this->buatAnak($this->orangTuaLain, 'Anak Orang Lain'), $this->orangTuaLain);

        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
            ->assertSee('Anak Saya')->assertDontSee('Anak Orang Lain')->assertSee('Menunggu validasi');
    }

    public function test_izin_menunggu_atau_disetujui_dapat_dibatalkan(): void
    {
        $anakId = $this->buatAnak($this->orangTua);

        foreach (['menunggu', 'disetujui'] as $status) {
            $izin = $this->buatIzin($anakId, $this->orangTua, $status);
            Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
                ->call('bukaKonfirmasi', $izin->id, 'batalkan')->call('jalankanKonfirmasi')->assertSee('Izin penjemputan dibatalkan');
            $this->assertSame('dibatalkan', $izin->fresh()->status);
        }
    }

    public function test_izin_yang_sudah_dipakai_tidak_bisa_dibatalkan(): void
    {
        $anakId = $this->buatAnak($this->orangTua);
        $izin = $this->buatIzin($anakId, $this->orangTua, 'disetujui');
        $this->buatKehadiranDenganIzin($anakId, $izin);

        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
            ->call('bukaKonfirmasi', $izin->id, 'batalkan')->call('jalankanKonfirmasi')->assertSee('sudah dipakai');
        $this->assertSame('disetujui', $izin->fresh()->status);
    }

    public function test_izin_ditolak_dapat_dihapus_beserta_fotonya(): void
    {
        $anakId = $this->buatAnak($this->orangTua);
        Storage::disk('local')->put('izin-penjemputan/ktp.png', 'x');
        $izin = $this->buatIzin($anakId, $this->orangTua, 'ditolak');

        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
            ->call('bukaKonfirmasi', $izin->id, 'hapus')->call('jalankanKonfirmasi')->assertSee('Izin penjemputan dihapus');

        $this->assertDatabaseMissing('izin_penjemputan', ['id' => $izin->id]);
        Storage::disk('local')->assertMissing('izin-penjemputan/ktp.png');
    }

    public function test_izin_menunggu_tidak_bisa_langsung_dihapus(): void
    {
        $izin = $this->buatIzin($this->buatAnak($this->orangTua), $this->orangTua);

        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
            ->call('bukaKonfirmasi', $izin->id, 'hapus')->call('jalankanKonfirmasi')->assertSee('Batalkan izin terlebih dahulu');
        $this->assertDatabaseHas('izin_penjemputan', ['id' => $izin->id]);
    }

    public function test_tidak_bisa_mengubah_atau_melihat_foto_izin_orang_lain(): void
    {
        $izinLain = $this->buatIzin($this->buatAnak($this->orangTuaLain), $this->orangTuaLain);

        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
            ->call('bukaKonfirmasi', $izinLain->id, 'batalkan')->assertNotFound();
        Livewire::actingAs($this->orangTua)->test(IzinPenjemputanIndex::class)
            ->call('lihatFoto', $izinLain->id)->assertNotFound();
        $this->assertSame('menunggu', $izinLain->fresh()->status);
    }
}
