<?php

namespace App\Livewire\OrangTua;

use App\Models\Pendaftaran;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-11a: Orang tua melihat daftar pendaftarannya sendiri beserta status terkininya.
 */
#[Title('Pendaftaran Saya')]
class PendaftaranIndex extends Component
{
    public function render(): View
    {
        return view('livewire.orang-tua.pendaftaran-index', [
            'pendaftaran' => Pendaftaran::query()
                ->where('orang_tua_id', auth()->id())
                ->with('paketLayanan:id,nama,harga')
                ->latest('id')
                ->get(),
        ]);
    }
}
