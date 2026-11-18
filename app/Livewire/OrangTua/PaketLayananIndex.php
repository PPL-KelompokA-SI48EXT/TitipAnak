<?php

namespace App\Livewire\OrangTua;

use App\Models\PaketLayanan;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Paket Layanan')]
class PaketLayananIndex extends Component
{
    public function render(): View
    {
        return view('livewire.orang-tua.paket-layanan-index', [
            'paketLayanan' => PaketLayanan::query()
                ->where('aktif', true)
                ->orderBy('nama')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
