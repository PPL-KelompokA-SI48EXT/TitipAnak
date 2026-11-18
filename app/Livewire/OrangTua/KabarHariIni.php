<?php

namespace App\Livewire\OrangTua;

use App\Models\Anak;
use App\Models\JadwalKegiatan;
use App\Models\Kehadiran;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-16f: Orang tua melihat kabar anaknya hari ini: sudah tiba atau belum, kondisi saat tiba,
 * kegiatan yang sedang berlangsung, dan siapa yang menjemput.
 *
 * Hanya baca, memakai data presensi dan jadwal kegiatan yang sudah ada. Hanya anak aktif milik orang tua
 * yang login yang ditampilkan. Izin mengikuti "lihat jadwal anak" (peran orang tua) agar tidak perlu seeder baru.
 */
#[Title('Kabar Hari Ini')]
class KabarHariIni extends Component
{
    public const STATUS_BELUM = 'belum';

    public const STATUS_HADIR = 'hadir';

    public const STATUS_PULANG = 'pulang';

    /** Ikon kondisi saat tiba, selaras dengan form check-in pengasuh. */
    public const IKON_KONDISI = [
        'Sehat dan ceria' => '😊',
        'Batuk atau pilek' => '🤧',
        'Kurang tidur' => '😴',
        'Rewel' => '😢',
        'Demam ringan' => '🤒',
    ];

    public function render(): View
    {
        $orangTua = auth()->user();
        abort_unless($orangTua?->can('lihat jadwal anak'), 403);

        $hariIni = today();

        $anak = Anak::query()
            ->where('orang_tua_id', $orangTua->id)
            ->where('status', 'aktif')
            ->with('kelompokUsia:id,nama')
            ->orderBy('nama_lengkap')
            ->get();

        $kehadiran = Kehadiran::query()
            ->whereIn('anak_id', $anak->modelKeys())
            ->padaTanggal($hariIni)
            ->get()
            ->keyBy('anak_id');

        $sekarang = now()->format('H:i:s');
        $kegiatan = JadwalKegiatan::harianUntukKelompok($anak->pluck('kelompok_usia_id')->filter(), $hariIni);

        $kabar = $anak->map(function (Anak $a) use ($kehadiran, $kegiatan, $sekarang) {
            $hadir = $kehadiran->get($a->id);
            $daftar = $kegiatan->get($a->kelompok_usia_id, collect());

            return [
                'anak' => $a,
                'kehadiran' => $hadir,
                'status' => match (true) {
                    $hadir === null => self::STATUS_BELUM,
                    $hadir->check_out_pada !== null => self::STATUS_PULANG,
                    default => self::STATUS_HADIR,
                },
                'berlangsung' => $daftar->first(fn ($g) => $g->jam_mulai <= $sekarang && $sekarang < $g->jam_selesai),
                'berikutnya' => $daftar->first(fn ($g) => $g->jam_mulai > $sekarang),
                'adaJadwal' => $daftar->isNotEmpty(),
            ];
        });

        return view('livewire.orang-tua.kabar-hari-ini', [
            'kabar' => $kabar,
            'hariIni' => $hariIni,
            'hariList' => \App\Models\JadwalShift::HARI,
            'ikonKondisi' => self::IKON_KONDISI,
        ]);
    }
}
