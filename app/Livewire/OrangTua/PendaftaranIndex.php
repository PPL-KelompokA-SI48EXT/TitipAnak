<?php

namespace App\Livewire\OrangTua;

use App\Models\Pendaftaran;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-11a: Orang tua melihat daftar pendaftarannya sendiri beserta status terkininya.
 * PBI-11b: Tiap pendaftaran menampilkan tahap saat ini dan tautan ke halaman status.
 * PBI-11c: Orang tua menghapus pendaftaran yang masih Draft jika batal mendaftar.
 */
#[Title('Pendaftaran Saya')]
class PendaftaranIndex extends Component
{
    /** Pendaftaran yang sedang menunggu konfirmasi hapus (null = dialog tertutup). */
    #[Locked]
    public ?int $hapusId = null;

    public function konfirmasiHapus(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola pendaftaran sendiri'), 403);

        $this->hapusId = $this->draftMilikSendiri($id)->id;
    }

    public function batalHapus(): void
    {
        $this->hapusId = null;
    }

    public function hapus(): void
    {
        abort_unless(auth()->user()?->can('kelola pendaftaran sendiri'), 403);
        abort_if($this->hapusId === null, 404);

        // Dicek ulang: status bisa saja sudah berubah sejak dialog dibuka.
        $this->draftMilikSendiri($this->hapusId)->hapusDraft();

        $this->hapusId = null;
        session()->flash('sukses', 'Pendaftaran Draft berhasil dihapus.');
    }

    /** Pendaftaran milik orang tua lain atau yang sudah bukan Draft diperlakukan seolah tidak ada. */
    protected function draftMilikSendiri(int $id): Pendaftaran
    {
        return Pendaftaran::query()
            ->where('orang_tua_id', auth()->id())
            ->where('status', 'draft')
            ->findOrFail($id);
    }

    public function render(): View
    {
        return view('livewire.orang-tua.pendaftaran-index', [
            'pendaftaran' => Pendaftaran::query()
                ->where('orang_tua_id', auth()->id())
                ->with(['paketLayanan:id,nama,harga', 'anak:id,pendaftaran_id,nama_lengkap'])
                ->latest('id')
                ->get(),
            'hapusPendaftaran' => $this->hapusId
                ? Pendaftaran::query()
                    ->where('orang_tua_id', auth()->id())
                    ->with(['paketLayanan:id,nama', 'anak:id,pendaftaran_id,nama_lengkap'])
                    ->find($this->hapusId)
                : null,
        ]);
    }
}
