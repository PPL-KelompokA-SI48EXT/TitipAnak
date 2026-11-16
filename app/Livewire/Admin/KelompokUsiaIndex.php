<?php

namespace App\Livewire\Admin;

use App\Models\KelompokUsia;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PK4-54: Admin melihat daftar kelompok usia dalam bentuk tabel.
 */
#[Title('Kelompok Usia')]
class KelompokUsiaIndex extends Component
{
    public function render(): View
    {
        return view('livewire.admin.kelompok-usia-index', [
            'kelompok' => KelompokUsia::query()
                ->orderBy('usia_min_bulan')
                ->orderBy('nama')
                ->get(),
            'total' => KelompokUsia::count(),
        ]);
    }
}
