<?php

namespace App\Livewire\OrangTua;

use App\Models\Anak;
use App\Models\Kehadiran;
use App\Models\LampiranLaporan;
use App\Models\LaporanHarian;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Laporan Anak')]
class LaporanAnakIndex extends Component
{
    #[Url(except: '')]
    public string $tanggal = '';

    #[Url(except: null)]
    public ?int $anakId = null;

    #[Locked]
    public ?int $fotoPreviewId = null;

    public function mount(): void
    {
        if ($this->tanggal === '') {
            $this->tanggal = today()->toDateString();
        }
    }

    public function pilihAnak(int $id): void
    {
        $this->anakId = $id;
        $this->fotoPreviewId = null;
    }

    public function lihatHariIni(): void
    {
        $this->tanggal = today()->toDateString();
        $this->fotoPreviewId = null;
    }

    public function bukaFoto(int $lampiranId): void
    {
        $this->fotoPreviewId = $lampiranId;
    }

    public function tutupFoto(): void
    {
        $this->fotoPreviewId = null;
    }

    private function tanggalTerpilih(): Carbon
    {
        try {
            return $this->tanggal !== '' ? Carbon::parse($this->tanggal)->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('lihat laporan anak'), 403);

        $user = auth()->user();
        $tanggal = $this->tanggalTerpilih();

        // Ambil semua anak aktif milik orang tua yang sedang login
        $daftarAnak = Anak::query()
            ->where('orang_tua_id', $user->id)
            ->where('status', 'aktif')
            ->with('kelompokUsia:id,nama')
            ->orderBy('nama_lengkap')
            ->get();

        // Tentukan anak aktif yang dipilih (validasi kepemilikan agar tidak bisa dimanipulasi via URL)
        $anakTerpilih = $this->anakId
            ? $daftarAnak->firstWhere('id', $this->anakId)
            : $daftarAnak->first();

        if ($anakTerpilih && $this->anakId !== $anakTerpilih->id) {
            $this->anakId = $anakTerpilih->id;
        }

        $kehadiran = null;
        $laporan = null;
        $fotoPreview = null;

        if ($anakTerpilih) {
            $kehadiran = Kehadiran::query()
                ->where('anak_id', $anakTerpilih->id)
                ->padaTanggal($tanggal)
                ->first();

            $laporan = LaporanHarian::query()
                ->where('anak_id', $anakTerpilih->id)
                ->padaTanggal($tanggal)
                ->with(['pengasuh:id,nama', 'lampiran'])
                ->first();

            if ($laporan && $this->fotoPreviewId) {
                $fotoPreview = $laporan->lampiran->firstWhere('id', $this->fotoPreviewId);
            }
        }

        // Ambil riwayat 7 laporan terakhir milik anak terpilih agar orang tua mudah meninjau hari sebelumnya
        $riwayatTanggal = $anakTerpilih
            ? LaporanHarian::query()
                ->where('anak_id', $anakTerpilih->id)
                ->orderByDesc('tanggal')
                ->limit(7)
                ->pluck('tanggal')
            : collect();

        return view('livewire.orang-tua.laporan-anak-index', [
            'tanggalObjek' => $tanggal,
            'daftarAnak' => $daftarAnak,
            'anakTerpilih' => $anakTerpilih,
            'kehadiran' => $kehadiran,
            'laporan' => $laporan,
            'fotoPreview' => $fotoPreview,
            'riwayatTanggal' => $riwayatTanggal,
        ]);
    }
}