<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    private function buatAkun(string $role, array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'password' => bcrypt('password'),
            'aktif' => true,
        ], $attrs))->assignRole($role);
    }

    public function test_halaman_login_dapat_dibuka_oleh_tamu(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke TitipAnak')
            ->assertSee('Alamat Email')
            ->assertSee('Kata Sandi');
    }

    public function test_pengguna_yang_sudah_login_diarahkan_ke_dashboard(): void
    {
        $user = $this->buatAkun('admin');

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_berhasil_login_dan_diarahkan_ke_dashboard(): void
    {
        $admin = $this->buatAkun('admin', ['email' => 'admin@titipanak.test']);

        Livewire::test(Login::class)
            ->set('email', 'admin@titipanak.test')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->terakhir_login);
    }

    public function test_pengasuh_berhasil_login_dan_diarahkan_ke_dashboard(): void
    {
        $pengasuh = $this->buatAkun('pengasuh', ['email' => 'pengasuh@titipanak.test']);

        Livewire::test(Login::class)
            ->set('email', 'pengasuh@titipanak.test')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($pengasuh);
    }

    public function test_orang_tua_berhasil_login_dan_diarahkan_ke_dashboard(): void
    {
        $ortu = $this->buatAkun('orang_tua', ['email' => 'ortu@titipanak.test']);

        Livewire::test(Login::class)
            ->set('email', 'ortu@titipanak.test')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($ortu);
    }

    public function test_login_gagal_jika_kata_sandi_salah(): void
    {
        $this->buatAkun('admin', ['email' => 'admin@titipanak.test']);

        Livewire::test(Login::class)
            ->set('email', 'admin@titipanak.test')
            ->set('password', 'sandisalah')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_gagal_jika_email_tidak_terdaftar(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'ngawur@titipanak.test')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_dapat_login(): void
    {
        $this->buatAkun('pengasuh', [
            'email' => 'nonaktif@titipanak.test',
            'aktif' => false,
        ]);

        Livewire::test(Login::class)
            ->set('email', 'nonaktif@titipanak.test')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_field_email_dan_password_wajib_diisi(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['email', 'password']);
    }

    public function test_pengguna_dapat_logout(): void
    {
        $user = $this->buatAkun('admin');

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}

