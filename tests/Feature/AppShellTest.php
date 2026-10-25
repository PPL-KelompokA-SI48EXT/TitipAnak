<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppShellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    private function akun(string $role, array $attrs = []): User
    {
        return User::factory()->create($attrs)->assignRole($role);
    }

    public function test_tamu_diarahkan_ke_pemilih_akun(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('dev.akun'));
    }

    public function test_pemilih_akun_menampilkan_akun_per_peran(): void
    {
        $admin = $this->akun('admin', ['nama' => 'Admin Uji']);
        $this->akun('pengasuh', ['nama' => 'Pengasuh Uji']);
        $this->akun('orang_tua', ['nama' => 'Ortu Uji']);
        $this->akun('pengasuh', ['nama' => 'Pengasuh Nonaktif', 'aktif' => false]);

        $this->get(route('dev.akun'))
            ->assertOk()
            ->assertSee('Admin Uji')
            ->assertSee('Pengasuh Uji')
            ->assertSee('Ortu Uji')
            ->assertDontSee('Pengasuh Nonaktif');
    }

    public function test_navbar_menampilkan_sebagai_peran_dan_footer(): void
    {
        $this->actingAs($this->akun('pengasuh', ['nama' => 'Siti Uji']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sebagai')
            ->assertSee('Pengasuh')
            ->assertSee('Siti Uji')
            ->assertSee('Tugas Besar PPL');
    }

    public function test_sidebar_admin_hanya_menu_admin(): void
    {
        $this->actingAs($this->akun('admin'))
            ->get(route('dashboard'))
            ->assertSee('Jadwal Shift')
            ->assertSee('Akun Pengguna')
            ->assertDontSee('Presensi')
            ->assertDontSee('Laporan Anak');
    }

    public function test_sidebar_pengasuh_hanya_menu_pengasuh(): void
    {
        $this->actingAs($this->akun('pengasuh'))
            ->get(route('dashboard'))
            ->assertSee('Presensi')
            ->assertSee('Laporan Harian')
            ->assertDontSee('Akun Pengguna')
            ->assertDontSee('Jadwal Shift');
    }

    public function test_sidebar_orang_tua_hanya_menu_orang_tua(): void
    {
        $this->actingAs($this->akun('orang_tua'))
            ->get(route('dashboard'))
            ->assertSee('Laporan Anak')
            ->assertSee('Data Anak')
            ->assertDontSee('Presensi')
            ->assertDontSee('Akun Pengguna');
    }

    public function test_ganti_akun_lewat_switcher(): void
    {
        $admin = $this->akun('admin');
        $ortu = $this->akun('orang_tua');

        $this->actingAs($admin)
            ->post(route('dev.masuk', $ortu))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($ortu);
    }

    public function test_akun_nonaktif_tidak_bisa_dipakai_masuk(): void
    {
        $nonaktif = $this->akun('pengasuh', ['aktif' => false]);

        $this->post(route('dev.masuk', $nonaktif))->assertForbidden();
        $this->assertGuest();
    }

    public function test_keluar_mengeluarkan_akun(): void
    {
        $this->actingAs($this->akun('admin'))
            ->post(route('dev.keluar'))
            ->assertRedirect(route('dev.akun'));

        $this->assertGuest();
    }
}
