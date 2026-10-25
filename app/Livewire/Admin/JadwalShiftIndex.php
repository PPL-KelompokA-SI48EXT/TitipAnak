<?php

namespace App\Livewire\Admin;

use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * PBI-14b: Admin melihat seluruh jadwal shift untuk memantau penugasan.
 */
#[Title('Jadwal Shift')]
class JadwalShiftIndex extends Component
{
    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $kelompok = '';

    #[Url(except: '')]
    public string $hari = '';

    public function resetFilter(): void
    {
        $this->reset('q', 'kelompok', 'hari');
    }

    public function render(): View
    {
        $semua = JadwalShift::count();

        $jadwal = JadwalShift::query()
            ->with(['pengasuh:id,nama', 'kelompokUsia:id,nama'])
            ->when($this->q !== '', fn ($query) => $query->whereHas(
                'pengasuh', fn ($p) => $p->where('nama', 'like', '%'.trim($this->q).'%')
            ))
            ->when($this->kelompok !== '', fn ($query) => $query->where('kelompok_usia_id', $this->kelompok))
            ->when($this->hari !== '', fn ($query) => $query->where('hari', $this->hari))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->orderBy('id')
            ->get();

        return view('livewire.admin.jadwal-shift-index', [
            'jadwal' => $jadwal,
            'semua' => $semua,
            'kelompokList' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'hariList' => JadwalShift::HARI,
        ]);
    }
}
