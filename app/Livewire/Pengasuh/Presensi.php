<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use App\Models\Kehadiran;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-16a: Pengasuh mencatat check-in anak (jam datang dan kondisi).
 *
 * Hanya anak di kelompok usia yang dipegang pengasuh hari ini yang muncul.
 */
#[Title('Presensi')]
class Presensi extends Component
{
    /** Pilihan cepat kondisi anak saat datang. */
    public const KONDISI = ['Sehat dan ceria', 'Batuk atau pilek', 'Kurang tidur', 'Rewel', 'Demam ringan'];

    /** Anak yang sedang diisi form check-in-nya. */
    #[Locked]
    public ?int $anakId = null;

    public string $jam_datang = '';

    public string $kondisi = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'jam_datang' => [
                'required',
                'date_format:H:i',
                // Jam datang tidak boleh lebih dari sekarang (presensi hanya untuk hari ini).
                fn (string $attribute, mixed $value, \Closure $fail) => $value <= now()->format('H:i')
                    ?: $fail('Jam datang tidak boleh melewati jam sekarang.'),
            ],
            'kondisi' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['jam_datang' => 'jam datang', 'kondisi' => 'kondisi anak'];
    }

    public function mulaiCheckIn(int $anakId): void
    {
        $anak = $this->anakDiasuh()->whereKey($anakId)->first();
        if (! $anak) {
            return;
        }

        $this->resetValidation();
        $this->anakId = $anak->id;
        $this->jam_datang = now()->format('H:i');
        $this->kondisi = '';
    }

    public function pakaiKondisi(string $kondisi): void
    {
        if (in_array($kondisi, self::KONDISI, true)) {
            $this->kondisi = $kondisi;
        }
    }

    public function batal(): void
    {
        $this->reset('anakId', 'jam_datang', 'kondisi');
        $this->resetValidation();
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        $data = $this->validate();

        // Anak harus masih di kelompok yang dipegang pengasuh hari ini (cegah id dimanipulasi).
        $anak = $this->anakId ? $this->anakDiasuh()->whereKey($this->anakId)->first() : null;
        if (! $anak) {
            $this->batal();
            session()->flash('sukses', 'Anak tidak ditemukan di kelompok Anda hari ini.');

            return;
        }

        Kehadiran::create([
            'anak_id' => $anak->id,
            'tanggal' => today(),
            'check_in_pada' => today()->setTimeFromTimeString($data['jam_datang']),
            'kondisi_check_in' => trim($data['kondisi']),
            'check_in_oleh_id' => auth()->id(),
        ]);

        $this->batal();
        session()->flash('sukses', "Check-in {$anak->nama_lengkap} berhasil dicatat.");
    }

    private function anakDiasuh()
    {
        return Anak::query()->diasuhOleh(auth()->user(), today());
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        $anak = $this->anakDiasuh()
            ->with(['kelompokUsia:id,nama', 'kehadiran' => fn ($q) => $q->padaTanggal(today())])
            ->orderBy('nama_lengkap')
            ->get();

        return view('livewire.pengasuh.presensi', [
            'anak' => $anak,
            'terpilih' => $this->anakId ? $anak->firstWhere('id', $this->anakId) : null,
            'kondisiList' => self::KONDISI,
            'hariIni' => today(),
        ]);
    }
}
