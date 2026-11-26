<?php

namespace App\Livewire\Admin;

use App\Models\KelompokUsia;
use App\Models\LaporanInsiden;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Laporan Insiden')]
class LaporanInsidenIndex extends Component
{
    #[Url(except: '')]
    public string $tanggal = '';

    #[Url(except: '')]
    public string $kelompokId = '';

    #[Url(except: '')]
    public string $q = '';

    #[Locked]
    public ?int $hapusInsidenId = null;

    public function resetFilter(): void
    {
        $this->reset('tanggal', 'kelompokId', 'q');
    }

    public function filterHariIni(): void
    {
        $this->tanggal = today()->toDateString();
    }

    // ───────────── Konfirmasi & Eksekusi Hapus Laporan Insiden ─────────────

    public function konfirmasiHapus(int $insidenId): void
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $insiden = LaporanInsiden::query()->whereKey($insidenId)->first();
        if (! $insiden) {
            session()->flash('galat', 'Laporan insiden tidak ditemukan atau sudah dihapus.');

            return;
        }

        $this->hapusInsidenId = $insiden->id;
    }

    public function batalHapus(): void
    {
        $this->hapusInsidenId = null;
    }

    public function hapusInsiden(): void
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $insiden = $this->hapusInsidenId
            ? LaporanInsiden::query()->with('anak')->whereKey($this->hapusInsidenId)->first()
            : null;

        if (! $insiden) {
            $this->batalHapus();
            session()->flash('galat', 'Laporan insiden tidak ditemukan atau sudah dihapus.');

            return;
        }

        $judul = $insiden->judul;
        $namaAnak = $insiden->anak?->nama_lengkap ?? 'Anak';

        $insiden->delete();
        $this->batalHapus();

        session()->flash('sukses', "Laporan insiden \"{$judul}\" ({$namaAnak}) berhasil dihapus.");
    }

    private function tanggalFilter(): ?Carbon
    {
        if ($this->tanggal === '') {
            return null;
        }

        try {
            return Carbon::parse($this->tanggal)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $tanggalObjek = $this->tanggalFilter();
        $kata = mb_strtolower(trim($this->q));

        $daftarInsiden = LaporanInsiden::query()
            ->with(['anak.kelompokUsia:id,nama', 'pengasuh:id,nama'])
            ->when($tanggalObjek !== null, fn ($q) => $q->padaTanggal($tanggalObjek))
            ->when($this->kelompokId !== '' && ctype_digit($this->kelompokId), function ($q) {
                $q->whereHas('anak', fn ($sub) => $sub->where('kelompok_usia_id', (int) $this->kelompokId));
            })
            ->when($kata !== '', function ($q) use ($kata) {
                $q->where(function ($sub) use ($kata) {
                    $sub->where('judul', 'like', "%{$kata}%")
                        ->orWhere('deskripsi', 'like', "%{$kata}%")
                        ->orWhereHas('anak', fn ($a) => $a->where('nama_lengkap', 'like', "%{$kata}%"))
                        ->orWhereHas('pengasuh', fn ($p) => $p->where('nama', 'like', "%{$kata}%"));
                });
            })
            ->orderByDesc('waktu_kejadian')
            ->get();

        // Tandai potensi duplikat (anak_id + tanggal + judul yang identik lebih dari 1 kali)
        $frekuensi = $daftarInsiden->countBy(
            fn (LaporanInsiden $i) => $i->anak_id.'|'.$i->waktu_kejadian->toDateString().'|'.mb_strtolower(trim($i->judul))
        );

        $daftarInsiden->each(function (LaporanInsiden $item) use ($frekuensi) {
            $kunci = $item->anak_id.'|'.$item->waktu_kejadian->toDateString().'|'.mb_strtolower(trim($item->judul));
            $item->setAttribute('potensi_duplikat', ($frekuensi[$kunci] ?? 0) > 1);
        });

        $insidenAkanDihapus = $this->hapusInsidenId
            ? LaporanInsiden::query()->with(['anak', 'pengasuh:id,nama'])->whereKey($this->hapusInsidenId)->first()
            : null;

        return view('livewire.admin.laporan-insiden-index', [
            'daftarInsiden' => $daftarInsiden,
            'kelompokList' => KelompokUsia::query()->orderBy('usia_min_bulan')->get(['id', 'nama']),
            'insidenAkanDihapus' => $insidenAkanDihapus,
            'totalTampil' => $daftarInsiden->count(),
            'totalDuplikat' => $daftarInsiden->where('potensi_duplikat', true)->count(),
        ]);
    }
}