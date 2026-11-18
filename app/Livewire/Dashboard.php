<?php

namespace App\Livewire;

use App\Models\Anak;
use App\Models\JadwalKegiatan;
use App\Models\JadwalShift;
use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\Pendaftaran;
use App\Models\Tagihan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * PBI-20: dashboard per peran (gambaran umum dalam satu layar).
 *
 * Hanya membaca data yang sudah ada. Bagian yang bergantung pada fitur yang belum jadi (pengumuman, laporan harian,
 * foto kegiatan) ditandai "segera hadir" di tampilan.
 */
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $peran = collect(['admin', 'pengasuh', 'orang_tua'])->first(fn (string $p) => $user->hasRole($p));

        return view('livewire.dashboard', [
            'peran' => $peran,
            'hariIni' => today(),
            'data' => match ($peran) {
                'admin' => $this->dataAdmin(),
                'pengasuh' => $this->dataPengasuh(),
                'orang_tua' => $this->dataOrangTua(),
                default => [],
            },
        ]);
    }

    /** Status kehadiran seorang anak hari ini: belum, hadir, atau pulang. */
    private function statusKehadiran(?Kehadiran $k): string
    {
        return match (true) {
            $k === null => 'belum',
            $k->check_out_pada !== null => 'pulang',
            default => 'hadir',
        };
    }

    /** @return array<string, mixed> */
    private function dataAdmin(): array
    {
        $hariIni = today();

        $anak = Anak::query()->where('status', 'aktif')->get(['id', 'kelompok_usia_id']);
        $kehadiran = Kehadiran::query()->padaTanggal($hariIni)->get(['id', 'anak_id', 'check_out_pada'])->keyBy('anak_id');

        $perKelompok = KelompokUsia::query()->orderBy('usia_min_bulan')->get(['id', 'nama'])
            ->map(function (KelompokUsia $k) use ($anak, $kehadiran) {
                $status = $anak->where('kelompok_usia_id', $k->id)->map(fn (Anak $a) => $this->statusKehadiran($kehadiran->get($a->id)));

                return [
                    'nama' => $k->nama,
                    'total' => $status->count(),
                    'hadir' => $status->filter(fn ($s) => $s === 'hadir')->count(),
                    'pulang' => $status->filter(fn ($s) => $s === 'pulang')->count(),
                    'belum' => $status->filter(fn ($s) => $s === 'belum')->count(),
                ];
            })
            ->filter(fn (array $k) => $k['total'] > 0)
            ->values();

        $statusAnak = $anak->map(fn (Anak $a) => $this->statusKehadiran($kehadiran->get($a->id)));

        $belumLunas = Tagihan::query()->where('status', 'belum_lunas');

        // Jumlah anak yang hadir per hari selama 7 hari terakhir (termasuk hari ini).
        $awal = $hariIni->copy()->subDays(6);
        $perHari = Kehadiran::query()->antara($awal, $hariIni)->get(['tanggal'])->countBy(fn (Kehadiran $k) => $k->tanggal->toDateString());
        $tujuhHari = collect(range(0, 6))->map(function (int $i) use ($awal, $perHari) {
            $tanggal = $awal->copy()->addDays($i);

            return ['tanggal' => $tanggal, 'jumlah' => (int) $perHari->get($tanggal->toDateString(), 0)];
        });

        return [
            'anakAktif' => $anak->count(),
            'hadir' => $statusAnak->filter(fn ($s) => $s === 'hadir')->count(),
            'pulang' => $statusAnak->filter(fn ($s) => $s === 'pulang')->count(),
            'pendaftaranMenunggu' => Pendaftaran::query()->where('status', 'menunggu_verifikasi')->count(),
            'tagihanBelumLunas' => (clone $belumLunas)->count(),
            'nominalBelumLunas' => (float) (clone $belumLunas)->sum('jumlah'),
            'pembayaranMenunggu' => DB::table('pembayaran')->where('status', 'menunggu')->count(),
            'perKelompok' => $perKelompok,
            'tujuhHari' => $tujuhHari,
            'maksHari' => max(1, (int) $tujuhHari->max('jumlah')),
        ];
    }

    /** @return array<string, mixed> */
    private function dataPengasuh(): array
    {
        $user = auth()->user();
        $hariIni = today();

        $shift = JadwalShift::tanggungJawab($user, $hariIni);
        $anak = Anak::query()->diasuhOleh($user, $hariIni)->with('kelompokUsia:id,nama')->orderBy('nama_lengkap')->get();
        $kehadiran = Kehadiran::query()->whereIn('anak_id', $anak->modelKeys())->padaTanggal($hariIni)->get()->keyBy('anak_id');

        $daftarAnak = $anak->map(fn (Anak $a) => [
            'anak' => $a,
            'kehadiran' => $kehadiran->get($a->id),
            'status' => $this->statusKehadiran($kehadiran->get($a->id)),
        ]);

        $sekarang = now()->format('H:i:s');
        $kegiatan = JadwalKegiatan::harianUntukKelompok($shift->pluck('kelompok_usia_id')->filter(), $hariIni);
        $perKelompok = $shift->unique('kelompok_usia_id')->map(function (JadwalShift $s) use ($kegiatan, $sekarang) {
            $daftar = $kegiatan->get($s->kelompok_usia_id, collect());

            return [
                'nama' => $s->kelompokUsia?->nama ?? '—',
                'berlangsung' => $daftar->first(fn ($g) => $g->jam_mulai <= $sekarang && $sekarang < $g->jam_selesai),
                'berikutnya' => $daftar->first(fn ($g) => $g->jam_mulai > $sekarang),
                'adaJadwal' => $daftar->isNotEmpty(),
            ];
        })->values();

        return [
            'shift' => $shift,
            'daftarAnak' => $daftarAnak,
            'jumlahAnak' => $daftarAnak->count(),
            'hadir' => $daftarAnak->where('status', 'hadir')->count(),
            'belum' => $daftarAnak->where('status', 'belum')->count(),
            'pulang' => $daftarAnak->where('status', 'pulang')->count(),
            'perKelompok' => $perKelompok,
        ];
    }

    /** @return array<string, mixed> */
    private function dataOrangTua(): array
    {
        $user = auth()->user();
        $hariIni = today();

        $anak = Anak::query()->where('orang_tua_id', $user->id)->where('status', 'aktif')->with('kelompokUsia:id,nama')->orderBy('nama_lengkap')->get();
        $kehadiran = Kehadiran::query()->whereIn('anak_id', $anak->modelKeys())->padaTanggal($hariIni)->get()->keyBy('anak_id');

        $pendaftaran = Pendaftaran::query()->where('orang_tua_id', $user->id)->get(['id', 'status']);
        $tagihan = Tagihan::query()
            ->whereIn('pendaftaran_id', $pendaftaran->modelKeys())
            ->where('status', 'belum_lunas')
            ->orderBy('jatuh_tempo')
            ->get(['id', 'jumlah', 'jatuh_tempo']);

        return [
            'anak' => $anak->map(fn (Anak $a) => [
                'anak' => $a,
                'kehadiran' => $kehadiran->get($a->id),
                'status' => $this->statusKehadiran($kehadiran->get($a->id)),
            ]),
            'pendaftaranPerStatus' => $pendaftaran->countBy('status'),
            'jumlahPendaftaran' => $pendaftaran->count(),
            'tagihanBelumLunas' => $tagihan->count(),
            'nominalBelumLunas' => (float) $tagihan->sum('jumlah'),
            'tagihanTerlambat' => $tagihan->filter(fn (Tagihan $t) => $t->jatuh_tempo !== null && $t->jatuh_tempo->lt($hariIni))->count(),
            'jatuhTempoTerdekat' => $tagihan->first(fn (Tagihan $t) => $t->jatuh_tempo !== null)?->jatuh_tempo,
        ];
    }
}
