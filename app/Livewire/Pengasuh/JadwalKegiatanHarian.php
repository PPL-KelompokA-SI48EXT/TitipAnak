<?php

namespace App\Livewire\Pengasuh;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-15e: Pengasuh melihat jadwal kegiatan harian sebagai panduan menjalankan rutinitas anak.
 */
#[Title('Jadwal Kegiatan Harian')]
class JadwalKegiatanHarian extends Component
{
    public function render(): View
    {
        $pengasuh = auth()->user();
        abort_unless($pengasuh?->can('lihat jadwal sendiri'), 403);

        $hariIni = today();

        return view('livewire.pengasuh.jadwal-kegiatan-harian', [
            'hariIni' => $hariIni,
            'sekarang' => now()->format('H:i:s'),
            'kegiatan' => JadwalKegiatan::harianUntukPengasuh($pengasuh, $hariIni),
            'hariList' => JadwalShift::HARI,
        ]);
    }
}
