<?php

namespace App\Livewire\Admin;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * PBI-15b: Admin melihat jadwal kegiatan setiap kelompok usia, dengan filter kelompok usia, hari, dan jenis.
 */
#[Title('Jadwal Kegiatan')]
class JadwalKegiatanIndex extends Component
{
    #[Url(except: '')]
    public string $kelompok = '';

    /** Filter hari memuat kegiatan hari itu ditambah kegiatan yang berlaku setiap hari. */
    #[Url(except: '')]
    public string $hari = '';

    #[Url(except: '')]
    public string $jenis = '';

    /** Kegiatan yang sedang menunggu konfirmasi hapus (null = dialog tertutup). */
    public ?int $hapusId = null;

    public function konfirmasiHapus(int $id): void
    {
        $this->hapusId = JadwalKegiatan::whereKey($id)->value('id');
    }

    public function batalHapus(): void
    {
        $this->hapusId = null;
    }

    public function hapus(): void
    {
        // Logika penghapusan menyusul di commit berikutnya (PK4-110 backend).
        $this->batalHapus();
    }

    public function resetFilter(): void
    {
        $this->reset('kelompok', 'hari', 'jenis');
    }

    public function render(): View
    {
        $kegiatan = JadwalKegiatan::query()
            ->when($this->kelompok !== '', fn ($q) => $q->where('kelompok_usia_id', $this->kelompok))
            ->when($this->hari !== '', fn ($q) => $q->where(fn ($w) => $w->whereNull('hari')->orWhere('hari', $this->hari)))
            ->when($this->jenis !== '', fn ($q) => $q->where('jenis_kegiatan', $this->jenis))
            ->orderByRaw('hari is not null')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->orderBy('id')
            ->get()
            ->groupBy('kelompok_usia_id');

        return view('livewire.admin.jadwal-kegiatan-index', [
            'kelompokList' => KelompokUsia::orderBy('usia_min_bulan')
                ->when($this->kelompok !== '', fn ($q) => $q->whereKey($this->kelompok))
                ->get(['id', 'nama', 'usia_min_bulan', 'usia_maks_bulan']),
            'semuaKelompok' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'kegiatan' => $kegiatan,
            'tampil' => $kegiatan->flatten(1)->count(),
            'semua' => JadwalKegiatan::count(),
            'hapusKegiatan' => $this->hapusId ? JadwalKegiatan::with('kelompokUsia:id,nama')->find($this->hapusId) : null,
            'hariList' => JadwalShift::HARI,
            'jenisList' => JadwalKegiatan::JENIS,
        ]);
    }
}
