<?php

namespace App\Livewire\OrangTua;

use App\Models\Anak;
use App\Models\LaporanPerkembangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

#[Title('Laporan Perkembangan Anak')]
class LaporanPerkembanganIndex extends Component
{
    #[Url(except: '')]
    public string $periode = ''; // Format YYYY-MM

    #[Url(except: null)]
    public ?int $anakId = null;

    public function pilihAnak(int $id): void
    {
        $this->anakId = $id;
        $this->periode = '';
    }

    public function pilihPeriode(string $periodeYm): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $periodeYm)) {
            $this->periode = $periodeYm;
        }
    }

    /**
     * Mengunduh rapor perkembangan bulanan anak dalam format PDF.
     */
    public function unduh(LaporanPerkembangan $laporanPerkembangan): Response
    {
        $user = auth()->user();
        abort_unless($user && $user->can('lihat laporan anak'), 403);

        $laporanPerkembangan->loadMissing(['anak.kelompokUsia', 'pengasuh']);

        abort_unless(
            $laporanPerkembangan->anak !== null && (int) $laporanPerkembangan->anak->orang_tua_id === (int) $user->id,
            403
        );

        $namaSlug = str($laporanPerkembangan->anak->nama_lengkap)->slug('-')->toString();
        $periodeSlug = $laporanPerkembangan->periode_bulan->format('Y-m');
        $namaBerkas = "Rapor-Perkembangan-{$namaSlug}-{$periodeSlug}.pdf";

        return Pdf::loadView('livewire.orang-tua.laporan-perkembangan-cetak', [
            'laporan' => $laporanPerkembangan,
            'anak' => $laporanPerkembangan->anak,
            'orangTua' => $user,
        ])
            ->setPaper('a4', 'portrait')
            ->download($namaBerkas);
    }

    private function periodeTerpilih(?Carbon $fallback): Carbon
    {
        try {
            if ($this->periode !== '' && preg_match('/^\d{4}-\d{2}$/', $this->periode)) {
                return Carbon::createFromFormat('Y-m', $this->periode)->startOfMonth();
            }
        } catch (\Throwable) {
            // Gunakan fallback jika parameter URL tidak valid
        }

        return $fallback ?? today()->startOfMonth();
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('lihat laporan anak'), 403);

        $user = auth()->user();

        $daftarAnak = Anak::query()
            ->where('orang_tua_id', $user->id)
            ->where('status', 'aktif')
            ->with('kelompokUsia:id,nama')
            ->orderBy('nama_lengkap')
            ->get();

        $anakTerpilih = $this->anakId
            ? $daftarAnak->firstWhere('id', $this->anakId)
            : $daftarAnak->first();

        if ($anakTerpilih && $this->anakId !== $anakTerpilih->id) {
            $this->anakId = $anakTerpilih->id;
        }

        $riwayatLaporan = $anakTerpilih
            ? LaporanPerkembangan::query()
                ->where('anak_id', $anakTerpilih->id)
                ->with('pengasuh:id,nama')
                ->orderByDesc('periode_bulan')
                ->get()
            : collect();

        $periodeTerbaru = $riwayatLaporan->first()?->periode_bulan;
        $periodeObjek = $this->periodeTerpilih($periodeTerbaru);

        if ($this->periode === '' && $periodeTerbaru !== null) {
            $this->periode = $periodeTerbaru->format('Y-m');
        }

        $laporanTerpilih = $riwayatLaporan->first(
            fn (LaporanPerkembangan $item) => $item->periode_bulan->isSameMonth($periodeObjek)
        );

        return view('livewire.orang-tua.laporan-perkembangan-index', [
            'daftarAnak' => $daftarAnak,
            'anakTerpilih' => $anakTerpilih,
            'periodeObjek' => $periodeObjek,
            'laporanTerpilih' => $laporanTerpilih,
            'riwayatLaporan' => $riwayatLaporan,
        ]);
    }
}