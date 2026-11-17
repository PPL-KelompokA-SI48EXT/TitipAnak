<?php

namespace Tests\Feature\OrangTua;

use App\Livewire\OrangTua\PenjemputIndex;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PenjemputIndexTest extends TestCase
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

    private function buatAnak(User $orangTua, string $nama = 'Nadia Pratama'): int
    {
        $paket = PaketLayanan::create([
            'nama' => 'Paket '.fake()->unique()->numberBetween(1000, 9999),
            'harga' => 100000,
            'aktif' => true,
        ]);

        $pendaftaranId = DB::table('pendaftaran')->insertGetId([
            'orang_tua_id' => $orangTua->id,
            'paket_layanan_id' => $paket->id,
            'status' => 'disetujui',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('anak')->insertGetId([
            'pendaftaran_id' => $pendaftaranId,
            'orang_tua_id' => $orangTua->id,
            'nama_lengkap' => $nama,
            'tanggal_lahir' => '2022-01-01',
            'jenis_kelamin' => 'P',
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function dataForm(int $anakId, array $override = []): array
    {
        return $override + [
            'anak_id' => (string) $anakId,
            'nama_lengkap' => 'Budi Pratama',
            'hubungan' => 'Kakek',
            'no_hp' => '081234567890',
            'foto_identitas' => UploadedFile::fake()->image('ktp.png'),
        ];
    }

    private function buatPenjemput(User $orangTua, string $nama = 'Budi Lama', string $path = 'penjemput-identitas/lama.png'): int
    {
        $anakId = $this->buatAnak($orangTua, 'Anak Penjemput');

        return DB::table('penjemput_terdaftar')->insertGetId([
            'anak_id' => $anakId,
            'nama_lengkap' => $nama,
            'hubungan' => 'Kakek',
            'no_hp' => '081234567890',
            'path_foto_identitas' => $path,
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_orang_tua_dapat_membuka_mode_ubah_penjemput_miliknya(): void
    {
        $penjemputId = $this->buatPenjemput($this->orangTua);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->call('edit', $penjemputId)
            ->assertSet('edit_id', (string) $penjemputId)
            ->assertSet('nama_lengkap', 'Budi Lama')
            ->assertSet('hubungan', 'Kakek')
            ->assertSet('no_hp', '081234567890')
            ->assertSet('foto_identitas', null);
    }

    public function test_orang_tua_dapat_mengubah_data_tanpa_mengganti_foto_lama(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('penjemput-identitas/lama.png', 'foto lama');
        $penjemputId = $this->buatPenjemput($this->orangTua);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->call('edit', $penjemputId)
            ->set([
                'nama_lengkap' => 'Budi Baru',
                'hubungan' => 'Paman',
                'no_hp' => '089876543210',
            ])
            ->call('perbarui')
            ->assertHasNoErrors()
            ->assertSee('Penjemput berhasil diperbarui.');

        $this->assertDatabaseHas('penjemput_terdaftar', [
            'id' => $penjemputId,
            'nama_lengkap' => 'Budi Baru',
            'hubungan' => 'Paman',
            'no_hp' => '089876543210',
            'path_foto_identitas' => 'penjemput-identitas/lama.png',
        ]);
        Storage::disk('local')->assertExists('penjemput-identitas/lama.png');
    }

    public function test_orang_tua_dapat_mengganti_foto_identitas(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('penjemput-identitas/lama.png', 'foto lama');
        $penjemputId = $this->buatPenjemput($this->orangTua);
        $fotoBaru = UploadedFile::fake()->image('ktp-baru.png');

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->call('edit', $penjemputId)
            ->set('foto_identitas', $fotoBaru)
            ->call('perbarui')
            ->assertHasNoErrors();

        $pathBaru = DB::table('penjemput_terdaftar')->whereKey($penjemputId)->value('path_foto_identitas');

        $this->assertNotSame('penjemput-identitas/lama.png', $pathBaru);
        Storage::disk('local')->assertMissing('penjemput-identitas/lama.png');
        Storage::disk('local')->assertExists($pathBaru);
    }

    public function test_orang_tua_tidak_dapat_mengubah_penjemput_milik_orang_tua_lain(): void
    {
        $penjemputId = $this->buatPenjemput($this->orangTuaLain);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->call('edit', $penjemputId)
            ->assertStatus(404);
    }

    public function test_orang_tua_dapat_membuka_halaman_penjemput(): void
    {
        $anakId = $this->buatAnak($this->orangTua);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.penjemput.index'))
            ->assertOk()
            ->assertSee('Daftar Penjemput')
            ->assertSee('Nadia Pratama');
    }

    public function test_tamu_ditolak_oleh_middleware_auth(): void
    {
        $this->get(route('orang-tua.penjemput.index'))
            ->assertRedirect(route('login'));
    }

    public function test_pengguna_tanpa_permission_ditolak(): void
    {
        $this->actingAs($this->pengasuh)
            ->get(route('orang-tua.penjemput.index'))
            ->assertForbidden();
    }

    public function test_daftar_hanya_menampilkan_penjemput_milik_anak_user_login(): void
    {
        $anakSaya = $this->buatAnak($this->orangTua, 'Anak Saya');
        $anakOrangLain = $this->buatAnak($this->orangTuaLain, 'Anak Orang Lain');

        DB::table('penjemput_terdaftar')->insert([
            [
                'anak_id' => $anakSaya,
                'nama_lengkap' => 'Penjemput Saya',
                'hubungan' => 'Paman',
                'no_hp' => '081111111111',
                'path_foto_identitas' => 'penjemput-identitas/saya.png',
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'anak_id' => $anakOrangLain,
                'nama_lengkap' => 'Penjemput Orang Lain',
                'hubungan' => 'Kakak',
                'no_hp' => '082222222222',
                'path_foto_identitas' => 'penjemput-identitas/orang-lain.png',
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($this->orangTua)
            ->get(route('orang-tua.penjemput.index'))
            ->assertSee('Penjemput Saya')
            ->assertDontSee('Penjemput Orang Lain');
    }

    public function test_orang_tua_dapat_menyimpan_penjemput_dengan_foto_private(): void
    {
        Storage::fake('local');
        $anakId = $this->buatAnak($this->orangTua);
        $foto = UploadedFile::fake()->image('ktp.png');

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->set($this->dataForm($anakId, ['foto_identitas' => $foto]))
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('Penjemput berhasil ditambahkan.');

        $penjemput = DB::table('penjemput_terdaftar')->first();

        $this->assertNotNull($penjemput);
        $this->assertSame('Budi Pratama', $penjemput->nama_lengkap);
        $this->assertSame($anakId, $penjemput->anak_id);
        $this->assertTrue((bool) $penjemput->aktif);
        Storage::disk('local')->assertExists($penjemput->path_foto_identitas);
    }

    public function test_semua_field_wajib_diisi(): void
    {
        $anakId = $this->buatAnak($this->orangTua);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->set(['anak_id' => (string) $anakId])
            ->call('simpan')
            ->assertHasErrors(['nama_lengkap', 'hubungan', 'no_hp', 'foto_identitas']);

        $this->assertDatabaseCount('penjemput_terdaftar', 0);
    }

    public function test_foto_harus_berupa_jpg_jpeg_atau_png_maksimal_2_mb(): void
    {
        $anakId = $this->buatAnak($this->orangTua);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->set($this->dataForm($anakId, [
                'foto_identitas' => UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'),
            ]))
            ->call('simpan')
            ->assertHasErrors(['foto_identitas']);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->set($this->dataForm($anakId, [
                'foto_identitas' => UploadedFile::fake()->image('besar.png')->size(2049),
            ]))
            ->call('simpan')
            ->assertHasErrors(['foto_identitas']);
    }

    public function test_hubungan_maksimal_50_karakter(): void
    {
        $anakId = $this->buatAnak($this->orangTua);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->set($this->dataForm($anakId, ['hubungan' => str_repeat('A', 51)]))
            ->call('simpan')
            ->assertHasErrors(['hubungan']);

        $this->assertDatabaseCount('penjemput_terdaftar', 0);
    }

    public function test_anak_milik_orang_tua_lain_ditolak(): void
    {
        $anakOrangLain = $this->buatAnak($this->orangTuaLain);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->set($this->dataForm($anakOrangLain))
            ->call('simpan')
            ->assertHasErrors(['anak_id']);

        $this->assertDatabaseCount('penjemput_terdaftar', 0);
    }

    public function test_form_direset_setelah_berhasil(): void
    {
        Storage::fake('local');
        $anakId = $this->buatAnak($this->orangTua);

        Livewire::actingAs($this->orangTua)
            ->test(PenjemputIndex::class)
            ->set($this->dataForm($anakId))
            ->call('simpan')
            ->assertSet('anak_id', '')
            ->assertSet('nama_lengkap', '')
            ->assertSet('hubungan', '')
            ->assertSet('no_hp', '')
            ->assertSet('foto_identitas', null);
    }
}
