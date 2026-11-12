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
            'jam_datang' => ['required'],
            'kondisi' => ['required'],
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

        // Aturan lengkap dan penyimpanan menyusul di commit berikutnya (PK4-112 validasi dan penyimpanan).
        $this->validate();
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
