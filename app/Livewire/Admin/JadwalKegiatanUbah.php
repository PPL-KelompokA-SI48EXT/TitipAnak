<?php

namespace App\Livewire\Admin;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;

/**
 * PBI-15c: Admin mengubah jadwal kegiatan jika rutinitas berubah.
 *
 * Aturan validasi dan pengecekan bentrok sama dengan pembuatan jadwal kegiatan (JadwalKegiatanBuat).
 */
#[Title('Ubah Jadwal Kegiatan')]
class JadwalKegiatanUbah extends JadwalKegiatanBuat
{
    #[Locked]
    public int $kegiatanId;

    public function mount(JadwalKegiatan $kegiatan): void
    {
        $this->kegiatanId = $kegiatan->id;
        $this->kelompok_usia_id = (string) $kegiatan->kelompok_usia_id;
        $this->nama_kegiatan = $kegiatan->nama_kegiatan;
        $this->jenis_kegiatan = $kegiatan->jenis_kegiatan;
        $this->hari = $kegiatan->hari === null ? '' : (string) $kegiatan->hari;
        $this->jam_mulai = substr($kegiatan->jam_mulai, 0, 5);
        $this->jam_selesai = substr($kegiatan->jam_selesai, 0, 5);
        $this->catatan = (string) $kegiatan->catatan;
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kelola jadwal'), 403);

        // Penyimpanan perubahan menyusul di commit berikutnya (PK4-109 logika update dan validasi).
        $this->validate();
    }

    public function render(): View
    {
        return view('livewire.admin.jadwal-kegiatan-buat', [
            'kelompok' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'hariList' => JadwalShift::HARI,
            'jenisList' => JadwalKegiatan::JENIS,
            'preset' => [],
            'judul' => 'Ubah Jadwal Kegiatan',
            'subjudul' => 'Sesuaikan nama, hari, jam, atau catatan kegiatan jika rutinitas berubah.',
            'tombol' => 'Simpan perubahan',
        ]);
    }
}
