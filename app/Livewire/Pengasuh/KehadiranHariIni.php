<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * PBI-16b: Pengasuh melihat daftar kehadiran anak hari ini, dengan pencarian nama dan filter status.
 */
#[Title('Kehadiran Hari Ini')]
class KehadiranHariIni extends Component
{
    public const STATUS_BELUM = 'belum';

    public const STATUS_HADIR = 'hadir';

    public const STATUS_PULANG = 'pulang';

    public const STATUS = [self::STATUS_BELUM, self::STATUS_HADIR, self::STATUS_PULANG];

    #[Url(except: '')]
    public string $q = '';

    /** Kosong = semua status. */
    #[Url(except: '')]
    public string $status = '';

    public function pilihStatus(string $status): void
    {
        $this->status = in_array($status, self::STATUS, true) && $this->status !== $status ? $status : '';
    }

    public function resetFilter(): void
    {
        $this->reset('q', 'status');
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        $semua = Anak::query()
            ->diasuhOleh(auth()->user(), today())
            ->with(['kelompokUsia:id,nama', 'kehadiran' => fn ($q) => $q->padaTanggal(today())])
            ->orderBy('nama_lengkap')
            ->get()
            ->each(function (Anak $a) {
                $hadir = $a->kehadiran->first();
                $a->setAttribute('presensi_hari_ini', $hadir);
                $a->setAttribute('status_hari_ini', match (true) {
                    $hadir === null => self::STATUS_BELUM,
                    $hadir->sudahPulang() => self::STATUS_PULANG,
                    default => self::STATUS_HADIR,
                });
            });

        $status = in_array($this->status, self::STATUS, true) ? $this->status : '';
        $kata = mb_strtolower(trim($this->q));

        $anak = $semua
            ->when($status !== '', fn ($c) => $c->where('status_hari_ini', $status))
            ->when($kata !== '', fn ($c) => $c->filter(fn (Anak $a) => str_contains(mb_strtolower($a->nama_lengkap), $kata)))
            ->values();

        return view('livewire.pengasuh.kehadiran-hari-ini', [
            'anak' => $anak,
            'semua' => $semua->count(),
            'jumlah' => collect(self::STATUS)->mapWithKeys(fn ($s) => [$s => $semua->where('status_hari_ini', $s)->count()]),
            'statusAktif' => $status,
            'hariIni' => today(),
        ]);
    }
}
