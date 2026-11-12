<?php

namespace App\Livewire\Admin;

use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-15a: Admin membuat jadwal kegiatan per kelompok usia (sarapan, bermain, tidur siang, dst).
 */
#[Title('Buat Jadwal Kegiatan')]
class JadwalKegiatanBuat extends Component
{
    /** Pilihan cepat untuk kegiatan rutin: nama => [jenis, jam mulai, jam selesai]. */
    public const PRESET = [
        'Sarapan' => ['makan', '07:30', '08:00'],
        'Bermain' => ['bermain', '08:00', '09:30'],
        'Tidur siang' => ['tidur', '12:30', '14:30'],
    ];

    public string $kelompok_usia_id = '';

    public string $nama_kegiatan = '';

    public string $jenis_kegiatan = '';

    /** Kosong = berlaku setiap hari. */
    public string $hari = '';

    public string $jam_mulai = '07:30';

    public string $jam_selesai = '08:00';

    public string $catatan = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'kelompok_usia_id' => ['required'],
            'nama_kegiatan' => ['required'],
            'jenis_kegiatan' => ['required'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'kelompok_usia_id' => 'kelompok usia',
            'nama_kegiatan' => 'nama kegiatan',
            'jenis_kegiatan' => 'jenis kegiatan',
            'jam_mulai' => 'jam mulai',
            'jam_selesai' => 'jam selesai',
        ];
    }

    /** Isi nama, jenis, dan jam dari kegiatan rutin yang dipilih. */
    public function pakaiPreset(string $nama): void
    {
        if (! isset(self::PRESET[$nama])) {
            return;
        }

        [$jenis, $mulai, $selesai] = self::PRESET[$nama];

        $this->nama_kegiatan = $nama;
        $this->jenis_kegiatan = $jenis;
        $this->jam_mulai = $mulai;
        $this->jam_selesai = $selesai;
        $this->resetValidation();
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kelola jadwal'), 403);

        // Aturan lengkap dan penyimpanan menyusul di commit berikutnya (PK4-107 validasi dan penyimpanan).
        $this->validate();
    }

    public function render(): View
    {
        return view('livewire.admin.jadwal-kegiatan-buat', [
            'kelompok' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'hariList' => JadwalShift::HARI,
            'jenisList' => JadwalKegiatan::JENIS,
            'preset' => array_keys(self::PRESET),
        ]);
    }
}
