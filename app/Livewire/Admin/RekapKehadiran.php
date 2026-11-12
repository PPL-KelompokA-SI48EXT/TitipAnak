<?php

namespace App\Livewire\Admin;

use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * PBI-16f: Admin melihat rekap kehadiran anak, dengan filter rentang tanggal, kelompok usia, dan nama anak.
 */
#[Title('Rekap Kehadiran')]
class RekapKehadiran extends Component
{
    use WithPagination;

    public const PER_HALAMAN = 20;

    /** Kosong = awal bulan ini. */
    #[Url(except: '')]
    public string $dari = '';

    /** Kosong = hari ini. */
    #[Url(except: '')]
    public string $sampai = '';

    #[Url(except: '')]
    public string $kelompok = '';

    #[Url(except: '')]
    public string $q = '';

    /** catatan = satu baris per check-in; anak = ringkasan per anak. */
    #[Url(except: 'catatan')]
    public string $tampilan = 'catatan';

    public function setTampilan(string $tampilan): void
    {
        $this->tampilan = $tampilan === 'anak' ? 'anak' : 'catatan';
    }

    public function updated(string $properti): void
    {
        if (in_array($properti, ['dari', 'sampai', 'kelompok', 'q'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilter(): void
    {
        $this->reset('dari', 'sampai', 'kelompok', 'q');
        $this->resetPage();
    }

    private function tanggal(string $nilai, CarbonInterface $bawaan): CarbonInterface
    {
        try {
            return $nilai !== '' ? Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay() : $bawaan;
        } catch (\Throwable) {
            return $bawaan;
        }
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $dari = $this->tanggal($this->dari, today()->startOfMonth());
        $sampai = $this->tanggal($this->sampai, today());

        // Rentang terbalik dibaca apa adanya agar admin langsung melihat bahwa hasilnya kosong, bukan dibetulkan diam-diam.
        $filter = [
            'dari' => $dari,
            'sampai' => $sampai,
            'kelompok' => $this->kelompok !== '' ? (int) $this->kelompok : null,
            'q' => $this->q,
        ];

        $perAnak = $this->tampilan === 'anak';

        return view('livewire.admin.rekap-kehadiran', [
            'perAnak' => $perAnak,
            'baris' => $perAnak ? null : Kehadiran::rekap($filter)->paginate(self::PER_HALAMAN),
            'ringkasan' => $perAnak ? Kehadiran::ringkasanPerAnak($filter) : collect(),
            'total' => $perAnak ? Kehadiran::rekap($filter)->count() : null,
            'awal' => $dari,
            'akhir' => $sampai,
            'terbalik' => $dari->gt($sampai),
            'kelompokList' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'adaFilter' => $this->dari !== '' || $this->sampai !== '' || $this->kelompok !== '' || $this->q !== '',
        ]);
    }
}
