<?php

namespace App\Livewire\Pengasuh;

use App\Models\Anak;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-16b: Pengasuh melihat daftar kehadiran anak hari ini.
 */
#[Title('Kehadiran Hari Ini')]
class KehadiranHariIni extends Component
{
    public const STATUS_BELUM = 'belum';

    public const STATUS_HADIR = 'hadir';

    public const STATUS_PULANG = 'pulang';

    public function render(): View
    {
        abort_unless(auth()->user()?->can('presensi anak'), 403);

        $anak = Anak::query()
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

        return view('livewire.pengasuh.kehadiran-hari-ini', [
            'anak' => $anak,
            'hariIni' => today(),
        ]);
    }
}
