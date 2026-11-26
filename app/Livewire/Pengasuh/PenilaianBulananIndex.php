<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use App\Models\JadwalShift;
use App\Models\LaporanPerkembangan;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Penilaian Bulanan')]
class PenilaianBulananIndex extends Component
{
    #[Url(except: '')]
    public string $periode = ''; // Format YYYY-MM

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $status = ''; // '' | 'belum' | 'sudah'

    // ───────────── State Modal Form Buat / Ubah Penilaian ─────────────
    #[Locked]
    public ?int $anakId = null;

    #[Locked]
    public ?int $penilaianId = null;

    public string $catatan_motorik = '';

    public string $catatan_bahasa = '';

    public string $catatan_sosial = '';

    public string $catatan_kognitif = '';

    public string $catatan_umum = '';

    public function mount(): void
    {
        if ($this->periode === '') {$this->periode = today()->format('Y-m');
        }
    }

    public function pilihStatus(string $status): void
    {
        $this->status = in_array($status, ['belum', 'sudah'], true) &&$this->status !== $status ? $status : '';
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'status');$this->periode = today()->format('Y-m');
    }

    // ───────────── Buka & Tutup Form Penilaian ─────────────

    public function bukaForm(int $anakId): void
    {
        abort_unless(auth()->user()?->can('buat penilaian bulanan'), 403);

        $periode = $this->periodeTerpilih();$anak = $this->anakKelompokPengasuh()->whereKey($anakId)->first();

        if (! $anak) {
            session()->flash('galat', 'Anak tidak ditemukan di kelompok tanggung jawab Anda.');

            return;
        }

        $this->batalForm();
        $this->anakId =$anak->id;

        $existing = LaporanPerkembangan::query()
            ->where('anak_id', $anak->id)
            ->padaPeriode($periode)
            ->first();

        if ($existing) {
            $this->penilaianId =$existing->id;
            $this->catatan_motorik =$existing->catatan_motorik ?? '';
            $this->catatan_bahasa =$existing->catatan_bahasa ?? '';
            $this->catatan_sosial =$existing->catatan_sosial ?? '';
            $this->catatan_kognitif =$existing->catatan_kognitif ?? '';
            $this->catatan_umum =$existing->catatan_umum ?? '';
        }
    }

    public function batalForm(): void
    {
        $this->reset( 'anakId', 'penilaianId', 'catatan_motorik', 'catatan_bahasa', 'catatan_sosial', 'catatan_kognitif', 'catatan_umum' );$this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'catatan_motorik' => ['required', 'string', 'max:2000'],
            'catatan_bahasa' => ['required', 'string', 'max:2000'],
            'catatan_sosial' => ['required', 'string', 'max:2000'],
            'catatan_kognitif' => ['required', 'string', 'max:2000'],
            'catatan_umum' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'catatan_motorik' => 'perkembangan motorik',
            'catatan_bahasa' => 'perkembangan bahasa',
            'catatan_sosial' => 'perkembangan sosial & emosional',
            'catatan_kognitif' => 'perkembangan kognitif',
            'catatan_umum' => 'catatan umum',
        ];
    }

    public function simpanPenilaian(): void
    {
        abort_unless(auth()->user()?->can('buat penilaian bulanan'), 403);

        $data =$this->validate();
        $periode =$this->periodeTerpilih();

        $anak =$this->anakId
            ? $this->anakKelompokPengasuh()->whereKey($this->anakId)->first()
            : null;

        if (! $anak) {$this->batalForm();
            session()->flash('galat', 'Data anak tidak valid atau berada di luar kelompok Anda.');

            return;
        }

        $sudahAda = LaporanPerkembangan::query()
            ->where('anak_id', $anak->id)
            ->padaPeriode($periode)
            ->exists();

        LaporanPerkembangan::query()->updateOrCreate(
            [
                'anak_id' => $anak->id,
                'periode_bulan' => $periode->toDateString(),
            ],
            [
                'pengasuh_id' => auth()->id(),
                'catatan_motorik' => trim($data['catatan_motorik']),
                'catatan_bahasa' => trim($data['catatan_bahasa']),
                'catatan_sosial' => trim($data['catatan_sosial']),
                'catatan_kognitif' => trim($data['catatan_kognitif']),
                'catatan_umum' => filled($data['catatan_umum']) ? trim($data['catatan_umum']) : null,
            ]
        );

        $nama =$anak->nama_lengkap;
        $labelBulan = $periode->translatedFormat('F Y');$this->batalForm();

        session()->flash(
            'sukses',
            $sudahAda
                ? "Penilaian bulanan {$nama} ({$labelBulan}) berhasil diperbarui."
                : "Penilaian bulanan {$nama} ({$labelBulan}) berhasil disimpan."
        );
    }

    private function periodeTerpilih(): Carbon
    {
        try {
            if ($this->periode !== '' && preg_match('/^\d{4}-\d{2}$/',$this->periode)) {
                return Carbon::createFromFormat('Y-m', $this->periode)->startOfMonth();
            }
        } catch (\Throwable) {
            // Gunakan bulan berjalan sebagai fallback
        }

        return today()->startOfMonth();
    }

    /**
     * Ambil daftar anak aktif pada kelompok usia yang menjadi tanggung jawab shift pengasuh
     * (seluruh hari dalam seminggu, atau hari ini jika shift hanya terjadwal harian).
     */
    private function anakKelompokPengasuh()
    {
        $user = auth()->user();

        $kelompokIds = JadwalShift::query()
            ->where('pengasuh_id', $user->id)
            ->whereNotNull('kelompok_usia_id')
            ->pluck('kelompok_usia_id')
            ->unique();

        return Anak::query()
            ->where('status', 'aktif')
            ->when(
                $kelompokIds->isNotEmpty(),
                fn ($q) => $q->whereIn('kelompok_usia_id',$kelompokIds),
                fn ($q) => $q->diasuhOleh($user, today())
            );
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('buat penilaian bulanan'), 403);

        $periode =$this->periodeTerpilih();

        $semua =$this->anakKelompokPengasuh()
            ->with([
                'kelompokUsia:id,nama',
                'laporanPerkembangan' => fn ($q) => $q->padaPeriode($periode)->with('pengasuh:id,nama'),
            ])
            ->orderBy('nama_lengkap')
            ->get()
            ->each(function (Anak $a) {
                $penilaian =$a->laporanPerkembangan->first();
                $a->setAttribute('penilaian_bulan_ini',$penilaian);
                $a->setAttribute('sudah_dinilai',$penilaian !== null);
            });

        $kata = mb_strtolower(trim($this->q));

        $daftarAnak =$semua
            ->when($this->status === 'sudah', fn ($c) =>$c->where('sudah_dinilai', true))
            ->when($this->status === 'belum', fn ($c) =>$c->where('sudah_dinilai', false))
            ->when($kata !== '', fn ($c) => $c->filter(fn (Anak$a) => str_contains(mb_strtolower($a->nama_lengkap),$kata)))
            ->values();

        return view('livewire.pengasuh.penilaian-bulanan-index', [
            'periodeObjek' => $periode,
            'daftarAnak' => $daftarAnak,
            'totalAnak' => $semua->count(),
            'jumlahSudah' => $semua->where('sudah_dinilai', true)->count(),
            'jumlahBelum' => $semua->where('sudah_dinilai', false)->count(),
            'terpilihAnak' => $this->anakId ? $semua->firstWhere('id',$this->anakId) : null,
        ]);
    }
}