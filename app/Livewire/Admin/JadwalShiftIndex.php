<?php

namespace App\Livewire\Admin;

use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * PBI-14b: Admin melihat seluruh jadwal shift untuk memantau penugasan.
 *
 * Jadwal shift berpola mingguan (hari 1-7), jadi kalender hanya memetakan pola itu ke tanggal.
 * Tampilan: minggu (kolom per hari), bulan (grid bulanan), dan daftar (tabel).
 */
#[Title('Jadwal Shift')]
class JadwalShiftIndex extends Component
{
    public const MODE = ['minggu', 'bulan', 'daftar'];

    public const BULAN = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    #[Url(except: 'minggu')]
    public string $mode = 'minggu';

    /** Tanggal acuan kalender (Y-m-d). Kosong = hari ini. */
    #[Url(except: '')]
    public string $tanggal = '';

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $kelompok = '';

    /** Hanya dipakai pada tampilan daftar. */
    #[Url(except: '')]
    public string $hari = '';

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, self::MODE, true) ? $mode : 'minggu';
    }

    /** Geser kalender: $arah = -1 sebelumnya, 1 berikutnya (minggu atau bulan sesuai tampilan). */
    public function pindah(int $arah): void
    {
        $acuan = $this->acuan();

        $this->tanggal = ($this->mode === 'bulan'
            ? $acuan->copy()->startOfMonth()->addMonthsNoOverflow($arah)
            : $acuan->copy()->addWeeks($arah)
        )->toDateString();
    }

    public function hariIni(): void
    {
        $this->tanggal = '';
    }

    /** Klik tanggal di kalender bulan: buka minggu yang memuat tanggal itu. */
    public function pilihTanggal(string $tanggal): void
    {
        $this->tanggal = Carbon::parse($tanggal)->toDateString();
        $this->mode = 'minggu';
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'kelompok', 'hari');
    }

    private function acuan(): Carbon
    {
        try {
            return $this->tanggal !== '' ? Carbon::parse($this->tanggal)->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }

    public function render(): View
    {
        $mode = in_array($this->mode, self::MODE, true) ? $this->mode : 'minggu';
        $acuan = $this->acuan();

        $jadwal = JadwalShift::query()
            ->with(['pengasuh:id,nama', 'kelompokUsia:id,nama'])
            ->when($this->q !== '', fn ($query) => $query->whereHas(
                'pengasuh', fn ($p) => $p->where('nama', 'like', '%'.trim($this->q).'%')
            ))
            ->when($this->kelompok !== '', fn ($query) => $query->where('kelompok_usia_id', $this->kelompok))
            ->when($mode === 'daftar' && $this->hari !== '', fn ($query) => $query->where('hari', $this->hari))
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->orderBy('id')
            ->get();

        // Pekan yang memuat tanggal acuan (Senin-Minggu).
        $awalPekan = $acuan->copy()->startOfWeek(Carbon::MONDAY);
        $pekan = collect(range(0, 6))->map(fn ($i) => $awalPekan->copy()->addDays($i));

        // Grid bulan: minggu-minggu penuh yang menutupi bulan acuan.
        $hari = $acuan->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $akhir = $acuan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $grid = [];
        while ($hari->lte($akhir)) {
            $grid[] = collect(range(0, 6))->map(fn ($i) => $hari->copy()->addDays($i));
            $hari->addWeek();
        }

        return view('livewire.admin.jadwal-shift-index', [
            'mode' => $mode,
            'acuan' => $acuan,
            'jadwal' => $jadwal,
            'perHari' => $jadwal->groupBy('hari'),
            'semua' => JadwalShift::count(),
            'pekan' => $pekan,
            'grid' => $grid,
            'bulanList' => self::BULAN,
            'kelompokList' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'hariList' => JadwalShift::HARI,
        ]);
    }
}
