<?php

namespace App\Livewire\Admin;

use App\Models\KelompokUsia;
use App\Models\LaporanPerkembangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

#[Title('Laporan Perkembangan Anak')]
class LaporanPerkembanganIndex extends Component
{
    #[Url(except: '')]
    public string $periode = ''; // Format YYYY-MM (kosong = semua periode)

    #[Url(except: '')]
    public string $kelompokId = '';

    #[Url(except: '')]
    public string $statusKelengkapan = ''; // '' | 'lengkap' | 'kurang'

    #[Url(except: '')]
    public string $q = '';

    #[Locked]
    public ?int $hapusPenilaianId = null;

    public function pilihKelengkapan(string $nilai): void
    {
        $this->statusKelengkapan = in_array($nilai, ['lengkap', 'kurang'], true) && $this->statusKelengkapan !==$nilai
            ? $nilai
            : '';
    }

    public function resetFilter(): void
    {
        $this->reset('periode', 'kelompokId', 'statusKelengkapan', 'q');
    }

    public function filterPeriodeIni(): void
    {
        $this->periode = today()->format('Y-m');
    }

    /**
     * Mengunduh rapor perkembangan dalam format PDF untuk keperluan supervisi Admin.
     */
    public function unduh(LaporanPerkembangan $laporanPerkembangan): Response
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $laporanPerkembangan->loadMissing(['anak.kelompokUsia', 'anak.orangTua', 'pengasuh']);

        $namaSlug = str($laporanPerkembangan->anak?->nama_lengkap ?? 'anak')->slug('-')->toString();
        $periodeSlug = $laporanPerkembangan->periode_bulan->format('Y-m');$namaBerkas = "Rapor-Perkembangan-{$namaSlug}-{$periodeSlug}.pdf";

        return Pdf::loadView('livewire.orang-tua.laporan-perkembangan-cetak', [
            'laporan' => $laporanPerkembangan,
            'anak' => $laporanPerkembangan->anak,
            'orangTua' => $laporanPerkembangan->anak?->orangTua ?? auth()->user(),
        ])
            ->setPaper('a4', 'portrait')
            ->download($namaBerkas);
    }

    // ───────────── Konfirmasi & Eksekusi Hapus Penilaian Tidak Valid ─────────────

    public function konfirmasiHapus(int $penilaianId): void
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $penilaian = LaporanPerkembangan::query()->whereKey($penilaianId)->first();
        if (! $penilaian) {
            session()->flash('galat', 'Data laporan perkembangan tidak ditemukan atau sudah dihapus.');

            return;
        }

        $this->hapusPenilaianId =$penilaian->id;
    }

    public function batalHapus(): void
    {
        $this->hapusPenilaianId = null;
    }

    public function hapusPenilaian(): void
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $penilaian =$this->hapusPenilaianId
            ? LaporanPerkembangan::query()->with('anak')->whereKey($this->hapusPenilaianId)->first()
            : null;

        if (! $penilaian) {$this->batalHapus();
            session()->flash('galat', 'Data laporan perkembangan tidak ditemukan atau sudah dihapus.');

            return;
        }

        $namaAnak =$penilaian->anak?->nama_lengkap ?? 'Anak';
        $labelPeriode =$penilaian->periode_bulan->translatedFormat('F Y');

        $penilaian->delete();$this->batalHapus();

        session()->flash('sukses', "Laporan perkembangan {$namaAnak} periode {$labelPeriode} berhasil dihapus.");
    }

    private function periodeFilter(): ?Carbon
    {
        if ($this->periode === '' || ! preg_match('/^\d{4}-\d{2}$/',$this->periode)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m', $this->periode)->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $periodeObjek =$this->periodeFilter();
        $kata = mb_strtolower(trim($this->q));

        $semua = LaporanPerkembangan::query()
            ->with(['anak.kelompokUsia:id,nama', 'pengasuh:id,nama'])
            ->when($periodeObjek !== null, fn ($q) => $q->padaPeriode($periodeObjek))
            ->when($this->kelompokId !== '' && ctype_digit($this->kelompokId), function ($q) {
                $q->whereHas('anak', fn ($sub) => $sub->where('kelompok_usia_id', (int)$this->kelompokId));
            })
            ->when($kata !== '', function ($q) use ($kata) {$q->where(function ($sub) use ($kata) {
                    $sub->whereHas('anak', fn ($a) => $a->where('nama_lengkap', 'like', "\%{$kata}%"))
                        ->orWhereHas('pengasuh', fn ($p) => $p->where('nama', 'like', "\%{$kata}%"))
                        ->orWhere('catatan_umum', 'like', "%{$kata}%");
                });
            })
            ->orderByDesc('periode_bulan')
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (LaporanPerkembangan $item) {
                $kurangLengkap = blank($item->catatan_motorik)
                    || blank($item->catatan_bahasa)
                    || blank($item->catatan_sosial)
                    || blank($item->catatan_kognitif);

                $item->setAttribute('kurang_lengkap',$kurangLengkap);
            });

        $daftarPenilaian =$semua
            ->when($this->statusKelengkapan === 'lengkap', fn ($c) =>$c->where('kurang_lengkap', false))
            ->when($this->statusKelengkapan === 'kurang', fn ($c) =>$c->where('kurang_lengkap', true))
            ->values();

        $penilaianAkanDihapus =$this->hapusPenilaianId
            ? LaporanPerkembangan::query()->with(['anak', 'pengasuh:id,nama'])->whereKey($this->hapusPenilaianId)->first()
            : null;

        return view('livewire.admin.laporan-perkembangan-index', [
            'daftarPenilaian' => $daftarPenilaian,
            'kelompokList' => KelompokUsia::query()->orderBy('usia_min_bulan')->get(['id', 'nama']),
            'penilaianAkanDihapus' => $penilaianAkanDihapus,
            'totalSemua' => $semua->count(),
            'totalLengkap' => $semua->where('kurang_lengkap', false)->count(),
            'totalKurangLengkap' => $semua->where('kurang_lengkap', true)->count(),
        ]);
    }
}