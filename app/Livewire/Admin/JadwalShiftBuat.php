<?php

namespace App\Livewire\Admin;

use App\Models\JadwalShift;
use App\Models\KelompokUsia;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-14a: Admin membuat jadwal shift yang menghubungkan pengasuh dengan kelompok usia.
 */
#[Title('Buat Jadwal Shift')]
class JadwalShiftBuat extends Component
{
    public string $pengasuh_id = '';

    public string $kelompok_usia_id = '';

    public string $hari = '';

    public string $jam_mulai = '07:00';

    public string $jam_selesai = '15:00';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'pengasuh_id' => [
                'required',
                'integer',
                fn (string $attribute, mixed $value, \Closure $fail) => User::role('pengasuh')->where('aktif', true)->whereKey($value)->exists()
                    ?: $fail('Pilih pengasuh yang aktif.'),
            ],
            'kelompok_usia_id' => ['required', 'integer', Rule::exists('kelompok_usia', 'id')],
            'hari' => ['required', 'integer', Rule::in(array_keys(JadwalShift::HARI))],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'pengasuh_id' => 'pengasuh',
            'kelompok_usia_id' => 'kelompok usia',
            'jam_mulai' => 'jam mulai',
            'jam_selesai' => 'jam selesai',
        ];
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('kelola jadwal'), 403);

        $data = $this->validate();

        $mulai = $data['jam_mulai'].':00';
        $selesai = $data['jam_selesai'].':00';

        // Satu pengasuh tidak boleh punya dua shift yang saling bertabrakan di hari yang sama.
        $bentrok = JadwalShift::query()
            ->where('pengasuh_id', $data['pengasuh_id'])
            ->where('hari', $data['hari'])
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai)
            ->exists();

        if ($bentrok) {
            $this->addError('jam_mulai', 'Pengasuh ini sudah punya shift yang bertabrakan pada hari dan jam tersebut.');

            return;
        }

        JadwalShift::create([
            'pengasuh_id' => $data['pengasuh_id'],
            'kelompok_usia_id' => $data['kelompok_usia_id'],
            'hari' => $data['hari'],
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
        ]);

        $this->reset('pengasuh_id', 'kelompok_usia_id', 'hari');
        session()->flash('sukses', 'Jadwal shift berhasil dibuat.');
    }

    public function render(): View
    {
        return view('livewire.admin.jadwal-shift-buat', [
            'pengasuh' => User::role('pengasuh')->where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
            'kelompok' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'hariList' => JadwalShift::HARI,
        ]);
    }
}
