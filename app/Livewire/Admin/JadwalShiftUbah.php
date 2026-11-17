<?php

namespace App\Livewire\Admin;

use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;

/**
 * PBI-14c: Admin mengubah jadwal shift jika ada pergantian pengasuh.
 *
 * Aturan validasi dan pengecekan bentrok sama dengan pembuatan jadwal (JadwalShiftBuat).
 */
#[Title('Ubah Jadwal Shift')]
class JadwalShiftUbah extends JadwalShiftBuat
{
    #[Locked]
    public int $jadwalId;

    public function mount(JadwalShift $jadwal): void
    {
        $this->jadwalId = $jadwal->id;
        $this->pengasuh_id = (string) $jadwal->pengasuh_id;
        $this->kelompok_usia_id = (string) $jadwal->kelompok_usia_id;
        $this->hari = (string) $jadwal->hari;
        $this->jam_mulai = substr($jadwal->jam_mulai, 0, 5);
        $this->jam_selesai = substr($jadwal->jam_selesai, 0, 5);
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kelola jadwal'), 403);

        $data = $this->validate();

        $mulai = $data['jam_mulai'].':00';
        $selesai = $data['jam_selesai'].':00';

        if ($this->bentrok($data, $mulai, $selesai, $this->jadwalId)) {
            $this->addError('jam_mulai', 'Pengasuh ini sudah punya shift yang bertabrakan pada hari dan jam tersebut.');

            return;
        }

        JadwalShift::findOrFail($this->jadwalId)->update([
            'pengasuh_id' => $data['pengasuh_id'],
            'kelompok_usia_id' => $data['kelompok_usia_id'],
            'hari' => $data['hari'],
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
        ]);

        session()->flash('sukses', 'Jadwal shift berhasil diubah.');
        $this->redirectRoute('admin.jadwal-shift.index');
    }

    public function render(): View
    {
        $jadwal = JadwalShift::with('pengasuh:id,nama,aktif')->findOrFail($this->jadwalId);

        return view('livewire.admin.jadwal-shift-ubah', [
            'pengasuh' => User::role('pengasuh')->where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
            // Pengasuh lama yang sudah nonaktif tetap ditampilkan agar jelas siapa yang diganti.
            'pengasuhLama' => $jadwal->pengasuh,
            'kelompok' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'hariList' => JadwalShift::HARI,
        ]);
    }
}
