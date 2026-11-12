<?php

namespace App\Livewire\Admin;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-15b: Admin melihat jadwal kegiatan setiap kelompok usia.
 */
#[Title('Jadwal Kegiatan')]
class JadwalKegiatanIndex extends Component
{
    public function render(): View
    {
        $kegiatan = JadwalKegiatan::query()
            ->orderByRaw('hari is not null')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->orderBy('id')
            ->get()
            ->groupBy('kelompok_usia_id');

        return view('livewire.admin.jadwal-kegiatan-index', [
            'kelompokList' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama', 'usia_min_bulan', 'usia_maks_bulan']),
            'kegiatan' => $kegiatan,
            'semua' => $kegiatan->flatten(1)->count(),
            'hariList' => JadwalShift::HARI,
        ]);
    }
}
