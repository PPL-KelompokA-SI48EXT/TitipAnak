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

        $data = $this->validate();

        $mulai = $data['jam_mulai'].':00';
        $selesai = $data['jam_selesai'].':00';
        $hari = ($data['hari'] ?? '') === '' ? null : (int) $data['hari'];

        // Kegiatan ini sendiri dikecualikan, supaya mengubah catatan saja tidak dianggap bentrok.
        if (JadwalKegiatan::bertabrakan((int) $data['kelompok_usia_id'], $hari, $mulai, $selesai, $this->kegiatanId)->exists()) {
            $this->addError('jam_mulai', 'Kelompok usia ini sudah punya kegiatan yang bertabrakan pada hari dan jam tersebut.');

            return;
        }

        $kegiatan = JadwalKegiatan::find($this->kegiatanId);
        if (! $kegiatan) {
            $this->kegiatanHilang();

            return;
        }

        $kegiatan->update([
            'kelompok_usia_id' => $data['kelompok_usia_id'],
            'nama_kegiatan' => trim($data['nama_kegiatan']),
            'jenis_kegiatan' => $data['jenis_kegiatan'],
            'hari' => $hari,
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'catatan' => filled($data['catatan'] ?? null) ? trim($data['catatan']) : null,
        ]);

        session()->flash('sukses', 'Jadwal kegiatan berhasil diubah.');
        $this->redirectRoute('admin.jadwal-kegiatan.index');
    }

    /** Kegiatan dihapus admin lain saat form ini terbuka: kembali ke daftar dengan pesan, bukan halaman error. */
    private function kegiatanHilang(): void
    {
        session()->flash('galat', 'Kegiatan ini sudah dihapus, perubahan tidak disimpan.');
        $this->redirectRoute('admin.jadwal-kegiatan.index');
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
