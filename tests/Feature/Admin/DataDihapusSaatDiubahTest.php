<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JadwalKegiatanUbah;
use App\Livewire\Admin\JadwalShiftUbah;
use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Admin lain menghapus data saat form ubah masih terbuka: harus kembali ke daftar dengan pesan, bukan error 404/500. */
class DataDihapusSaatDiubahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $siti;

    private KelompokUsia $batita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->siti = User::factory()->create()->assignRole('pengasuh');
        $this->batita = KelompokUsia::create(['nama' => 'Batita', 'usia_min_bulan' => 12, 'usia_maks_bulan' => 23, 'kapasitas' => 12]);
    }

    public function test_simpan_kegiatan_yang_sudah_dihapus_kembali_ke_daftar_dengan_pesan(): void
    {
        $kegiatan = JadwalKegiatan::create([
            'kelompok_usia_id' => $this->batita->id, 'nama_kegiatan' => 'Sarapan', 'jenis_kegiatan' => 'makan', 'hari' => null,
            'jam_mulai' => '07:30:00', 'jam_selesai' => '08:00:00',
        ]);

        $form = Livewire::actingAs($this->admin)->test(JadwalKegiatanUbah::class, ['kegiatan' => $kegiatan]);
        $kegiatan->delete();

        $form->set('catatan', 'Baru')->call('simpan')->assertRedirect(route('admin.jadwal-kegiatan.index'));

        $this->assertSame('Kegiatan ini sudah dihapus, perubahan tidak disimpan.', session('galat'));
        $this->assertDatabaseCount('jadwal_kegiatan', 0);
    }

    public function test_simpan_shift_yang_sudah_dihapus_kembali_ke_daftar_dengan_pesan(): void
    {
        $jadwal = JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => 1,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);

        $form = Livewire::actingAs($this->admin)->test(JadwalShiftUbah::class, ['jadwal' => $jadwal]);
        $jadwal->delete();

        $form->set('jam_selesai', '16:00')->call('simpan')->assertRedirect(route('admin.jadwal-shift.index'));

        $this->assertSame('Jadwal shift ini sudah dihapus, perubahan tidak disimpan.', session('galat'));
        $this->assertDatabaseCount('jadwal_shift', 0);
    }

    public function test_render_ulang_form_shift_yang_sudah_dihapus_tidak_error(): void
    {
        $jadwal = JadwalShift::create([
            'pengasuh_id' => $this->siti->id, 'kelompok_usia_id' => $this->batita->id, 'hari' => 1,
            'jam_mulai' => '07:00:00', 'jam_selesai' => '15:00:00',
        ]);

        $form = Livewire::actingAs($this->admin)->test(JadwalShiftUbah::class, ['jadwal' => $jadwal]);
        $jadwal->delete();

        // Mengubah isian memicu render ulang; jadwalnya sudah tidak ada.
        $form->set('jam_mulai', '06:00')->assertRedirect(route('admin.jadwal-shift.index'));
    }

    public function test_pesan_galat_tampil_di_halaman_daftar(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['galat' => 'Kegiatan ini sudah dihapus, perubahan tidak disimpan.'])
            ->get(route('admin.jadwal-kegiatan.index'))
            ->assertSee('Kegiatan ini sudah dihapus');

        $this->actingAs($this->admin)
            ->withSession(['galat' => 'Jadwal shift ini sudah dihapus, perubahan tidak disimpan.'])
            ->get(route('admin.jadwal-shift.index'))
            ->assertSee('Jadwal shift ini sudah dihapus');
    }
}
