<?php

namespace App\Livewire\Pengasuh;

use App\Models\JadwalShift;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-14e: Pengasuh melihat jadwal shift sendiri: kelompok usia dan anak yang menjadi tanggung jawab hari ini.
 */
#[Title('Jadwal Saya')]
class JadwalSaya extends Component
{
    public function render(): View
    {
        $pengasuh = auth()->user();
        abort_unless($pengasuh?->can('lihat jadwal sendiri'), 403);

        return view('livewire.pengasuh.jadwal-saya', [
            'hariIni' => today(),
            'shiftHariIni' => JadwalShift::tanggungJawab($pengasuh, today()),
            'pola' => JadwalShift::polaMingguan($pengasuh)->groupBy('hari'),
            'hariList' => JadwalShift::HARI,
        ]);
    }
}
