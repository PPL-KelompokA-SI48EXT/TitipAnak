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
 */
#[Title('Status Pendaftaran')]
class PendaftaranDetail extends Component
{
    #[Locked]
    public int $pendaftaranId;

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

    public function render(): View
    {
        return view('livewire.orang-tua.pendaftaran-detail', [
            'pendaftaran' => $this->pendaftaranMilikSendiri($this->pendaftaranId),
        ]);
    }
}
