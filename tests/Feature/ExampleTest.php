<?php

namespace Tests\Feature;

use App\Models\KelompokUsia;
use App\Models\PaketLayanan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_tamu_melihat_landing_page_saat_akses_akar(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('TitipAnak')
            ->assertSee('Masuk ke aplikasi')
            ->assertSee(route('login'), false);
    }

    public function test_pengguna_yang_sudah_login_melihat_tombol_dashboard_di_landing_page(): void
    {
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Buka dashboard')
            ->assertSee(route('dashboard'), false)
            ->assertDontSee('Masuk ke aplikasi');
    }

    public function test_landing_page_menampilkan_paket_aktif_dan_kelompok_usia(): void
    {
        KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
        PaketLayanan::create(['nama' => 'Full Day', 'deskripsi' => 'Senin sampai Jumat', 'harga' => 2500000, 'aktif' => true]);
        PaketLayanan::create(['nama' => 'Paket Lama', 'harga' => 100000, 'aktif' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Batita')
            ->assertSee('12 bln – 23 bln')
            ->assertSee('Full Day')
            ->assertSee('Rp 2.500.000')
            ->assertDontSee('Paket Lama');
    }

    public function test_landing_page_tetap_tampil_tanpa_data_master(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="paket"', false)
            ->assertDontSee('id="kelompok"', false);
    }

    public function test_landing_page_menampilkan_carousel_foto_dan_pita_foto(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-carousel', false)
            ->assertSee('Suasana di daycare')
            ->assertSee('Momen kecil, senyum besar')
            ->assertSee('Komitmen kami untuk Anda')
            ->assertSee('images/landing/daycare-anak.jpg', false)
            ->assertSee('images/landing/pexels-8441840.jpg', false);
    }

    public function test_semua_foto_landing_page_ada_di_public(): void
    {
        $html = $this->get('/')->getContent();

        preg_match_all('#images/landing/([\w.\-]+\.jpg)#', $html, $cocok);
        $berkas = array_unique($cocok[1]);

        $this->assertGreaterThanOrEqual(15, count($berkas), 'Landing page minimal memuat 15 foto berbeda.');

        foreach ($berkas as $nama) {
            $this->assertFileExists(public_path('images/landing/'.$nama));
        }
    }

    public function test_tombol_daftar_paket_mengarah_ke_login_untuk_tamu(): void
    {
        PaketLayanan::create(['nama' => 'Full Day', 'deskripsi' => 'Senin sampai Jumat', 'harga' => 2500000, 'aktif' => true]);
        PaketLayanan::create(['nama' => 'Weekend Care', 'harga' => 1000000, 'aktif' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Daftar paket ini')
            ->assertSee('Cocok untuk orang tua yang bekerja penuh waktu')
            ->assertSee('Cocok untuk orang tua yang bekerja di akhir pekan')
            ->assertSee('Paling lengkap')
            ->assertSee('href="'.route('login').'"', false);
    }

    public function test_tombol_daftar_paket_mengarah_ke_halaman_paket_untuk_orang_tua(): void
    {
        PaketLayanan::create(['nama' => 'Full Day', 'harga' => 2500000, 'aktif' => true]);
        $orangTua = User::factory()->create()->assignRole('orang_tua');

        $this->actingAs($orangTua)->get('/')
            ->assertOk()
            ->assertSee('Daftar paket ini')
            ->assertSee('href="'.route('orang-tua.paket-layanan.index').'"', false);
    }

    public function test_paket_tunggal_tidak_diberi_label_paling_lengkap(): void
    {
        PaketLayanan::create(['nama' => 'Full Day', 'harga' => 2500000, 'aktif' => true]);

        $this->get('/')->assertOk()->assertDontSee('Paling lengkap');
    }

    public function test_landing_page_ditujukan_untuk_orang_tua_bukan_pengelola(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Siap mengelola daycare')
            ->assertDontSee('Admin daycare')
            ->assertDontSee('pengelola daycare')
            ->assertSee('Siap menitipkan si kecil dengan tenang?');
    }
}
