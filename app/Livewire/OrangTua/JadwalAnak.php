<?php

namespace App\Livewire\OrangTua;

use App\Models\Anak;
use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-15f: Orang tua melihat jadwal kegiatan harian dan pengasuh yang menjaga anaknya hari ini.
 *
 * Hanya baca. Hanya anak aktif milik orang tua yang login yang ditampilkan, dan hanya nama pengasuh yang dimuat.
 */
#[Title('Jadwal Anak')]
class JadwalAnak extends Component
{
    public function render(): View
    {
        $orangTua = auth()->user();
        abort_unless($orangTua?->can('lihat jadwal anak'), 403);

        $hariIni = today();

        $anak = Anak::query()
            ->where('orang_tua_id', $orangTua->id)
            ->where('status', 'aktif')
            ->with('kelompokUsia:id,nama')
            ->orderBy('nama_lengkap')
            ->get();

        $kelompokIds = $anak->pluck('kelompok_usia_id')->filter();

        return view('livewire.orang-tua.jadwal-anak', [
            'anak' => $anak,
            'hariIni' => $hariIni,
            'sekarang' => now()->format('H:i:s'),
            'pengasuh' => JadwalShift::bertugasUntukKelompok($kelompokIds, $hariIni),
            'kegiatan' => JadwalKegiatan::harianUntukKelompok($kelompokIds, $hariIni),
            'hariList' => JadwalShift::HARI,
        ]);
    }
}
