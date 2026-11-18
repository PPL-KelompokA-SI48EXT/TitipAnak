<?php

namespace App\Livewire\Admin;

use App\Models\CatatanKesehatan;
use App\Models\Pendaftaran;
use App\Models\PenjemputTerdaftar;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-11e: Admin memeriksa satu pendaftaran yang masuk: data orang tua, paket, anak,
 * catatan kesehatan, dan penjemput terdaftar. Halaman ini hanya menampilkan data.
 *
 * Draft belum diteruskan ke Admin, jadi diperlakukan seolah tidak ada (404).
 */
#[Title('Periksa Pendaftaran')]
class PendaftaranDetail extends Component
{
    #[Locked]
    public int $pendaftaranId;

    public function mount(int $pendaftaran): void
    {
        $this->pendaftaranId = $this->pendaftaranMasuk($pendaftaran)->id;
    }

    protected function pendaftaranMasuk(int $id): Pendaftaran
    {
        return Pendaftaran::query()
            ->whereIn('status', PendaftaranIndex::STATUS_MASUK)
            ->with([
                'orangTua:id,nama,email',
                'paketLayanan:id,nama,harga',
                'anak.kelompokUsia:id,nama',
            ])
            ->findOrFail($id);
    }

    public function render(): View
    {
        $pendaftaran = $this->pendaftaranMasuk($this->pendaftaranId);
        $anak = $pendaftaran->anak;

        return view('livewire.admin.pendaftaran-detail', [
            'pendaftaran' => $pendaftaran,
            'anak' => $anak,
            'profilOrangTua' => DB::table('profil_orang_tua')
                ->where('pengguna_id', $pendaftaran->orang_tua_id)
                ->first(['no_hp', 'alamat', 'pekerjaan', 'kontak_darurat']),
            'catatanKesehatan' => $anak ? CatatanKesehatan::where('anak_id', $anak->id)->first() : null,
            'penjemput' => $anak
                ? PenjemputTerdaftar::where('anak_id', $anak->id)->where('aktif', true)->orderBy('nama_lengkap')->get()
                : collect(),
            'diverifikasiOleh' => $pendaftaran->diverifikasi_oleh_id
                ? DB::table('pengguna')->where('id', $pendaftaran->diverifikasi_oleh_id)->value('nama')
                : null,
        ]);
    }
}
