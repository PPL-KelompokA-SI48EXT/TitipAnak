<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use App\Models\Kehadiran;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-16d: Pengasuh mencatat check-out anak dengan mencocokkan penjemput ke Penjemput Terdaftar.
 */
#[Title('Check-out')]
class CheckOut extends Component
{
    /** Pilihan "penjemput lain" pada daftar penjemput. */
    public const LAIN = 'lain';

    /** Kehadiran (anak) yang sedang diisi form check-out-nya. */
    #[Locked]
    public ?int $kehadiranId = null;

    public string $jam_pulang = '';

    /** Id penjemput terdaftar, atau "lain" bila yang datang tidak ada di daftar. */
    public string $penjemput = '';

    public string $nama_penjemput = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'jam_pulang' => ['required'],
            'penjemput' => ['required'],
            'nama_penjemput' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['jam_pulang' => 'jam pulang', 'penjemput' => 'penjemput', 'nama_penjemput' => 'nama penjemput'];
    }

    public function mulaiCheckOut(int $kehadiranId): void
    {
        $kehadiran = $this->kehadiranAktif()->whereKey($kehadiranId)->first();
        if (! $kehadiran) {
            return;
        }

        $this->resetValidation();
        $this->kehadiranId = $kehadiran->id;
        $this->jam_pulang = now()->format('H:i');
        $this->penjemput = '';
        $this->nama_penjemput = '';
    }

    public function batal(): void
    {
        $this->reset('kehadiranId', 'jam_pulang', 'penjemput', 'nama_penjemput');
        $this->resetValidation();
    }

    public function simpan(): void
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        // Pencocokan penjemput dan penyimpanan menyusul di commit berikutnya (PK4-115 logika pencocokan).
        $this->validate();
    }

    /** Kehadiran hari ini milik anak di kelompok pengasuh yang belum check-out. */
    private function kehadiranAktif()
    {
        return Kehadiran::query()
            ->padaTanggal(today())
            ->masihDiTempat()
            ->whereIn('anak_id', Anak::query()->diasuhOleh(auth()->user(), today())->select('id'));
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        $daftar = $this->kehadiranAktif()
            ->with(['anak.kelompokUsia:id,nama'])
            ->orderBy('check_in_pada')
            ->get();

        $terpilih = $this->kehadiranId ? $daftar->firstWhere('id', $this->kehadiranId) : null;

        return view('livewire.pengasuh.check-out', [
            'daftar' => $daftar,
            'terpilih' => $terpilih,
            'penjemputList' => $terpilih
                ? $terpilih->anak->penjemputTerdaftar()->aktif()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'hubungan'])
                : collect(),
            'hariIni' => today(),
        ]);
    }
}
