<?php

namespace App\Livewire\Pengasuh;

use App\Models\PenjemputTerdaftar;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Daftar Penjemput Terdaftar')]
class PenjemputIndex extends Component
{
    public function render(): View
    {
        abort_unless(auth()->user()?->can('validasi izin penjemputan'), 403);

        return view('livewire.pengasuh.penjemput-index', [
            'penjemput' => PenjemputTerdaftar::query()
                ->where('aktif', true)
                ->whereHas('anak', fn ($query) => $query->where('status', 'aktif'))
                ->with('anak:id,nama_lengkap')
                ->orderBy('anak_id')
                ->orderBy('nama_lengkap')
                ->get(),
        ]);
    }
}
