<?php

namespace Tests\Feature;

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

    public function test_tamu_diarahkan_ke_halaman_login_saat_akses_akar(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_pengguna_yang_sudah_login_diarahkan_ke_dashboard_saat_akses_akar(): void
    {
        $user = User::factory()->create()->assignRole('admin');

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}

