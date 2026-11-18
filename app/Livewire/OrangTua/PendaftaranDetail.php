<?php

namespace App\Livewire\OrangTua;

use App\Models\Pendaftaran;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-11b: Orang tua melihat status pendaftarannya (Draft, Menunggu Verifikasi, Disetujui)
 * agar tahu sudah sampai tahap mana.
 * PBI-11c: Pendaftaran yang masih Draft bisa dihapus dari halaman ini.
 */
#[Title('Status Pendaftaran')]
class PendaftaranDetail extends Component
{
    #[Locked]
    public int $pendaftaranId;

    /** Dialog konfirmasi hapus sedang terbuka. */
    public bool $konfirmasiHapusTerbuka = false;

    public function mount(int $pendaftaran): void
    {
        // Pendaftaran milik orang tua lain diperlakukan seolah tidak ada.
        $this->pendaftaranId = $this->pendaftaranMilikSendiri($pendaftaran)->id;
    }

    protected function pendaftaranMilikSendiri(int $id): Pendaftaran
    {
        return Pendaftaran::query()
            ->where('orang_tua_id', auth()->id())
            ->with(['paketLayanan:id,nama,harga', 'anak:id,pendaftaran_id,nama_lengkap'])
            ->findOrFail($id);
    }

    public function konfirmasiHapus(): void
    {
        abort_unless(auth()->user()?->can('kelola pendaftaran sendiri'), 403);
        abort_unless($this->pendaftaranMilikSendiri($this->pendaftaranId)->bisaDihapus(), 404);

        $this->konfirmasiHapusTerbuka = true;
    }

    public function batalHapus(): void
    {
        $this->konfirmasiHapusTerbuka = false;
    }

    public function hapus(): void
    {
        abort_unless(auth()->user()?->can('kelola pendaftaran sendiri'), 403);

        // Dicek ulang: status bisa saja sudah berubah sejak dialog dibuka.
        $pendaftaran = $this->pendaftaranMilikSendiri($this->pendaftaranId);
        abort_unless($pendaftaran->bisaDihapus(), 404);

        $pendaftaran->hapusDraft();

        session()->flash('sukses', 'Pendaftaran Draft berhasil dihapus.');

        $this->redirectRoute('orang-tua.pendaftaran.index');
    }

    public function render(): View
    {
        return view('livewire.orang-tua.pendaftaran-detail', [
            'pendaftaran' => $this->pendaftaranMilikSendiri($this->pendaftaranId),
        ]);
    }
}
