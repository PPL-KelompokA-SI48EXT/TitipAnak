<?php

namespace App\Livewire\Admin;

use App\Models\Kehadiran;
use App\Models\KelompokUsia;
use App\Models\LogPenghapusanKehadiran;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * PBI-16f: Admin melihat rekap kehadiran anak, dengan filter rentang tanggal, kelompok usia, dan nama anak.
 */
#[Title('Rekap Kehadiran')]
class RekapKehadiran extends Component
{
    use WithPagination;

    public const PER_HALAMAN = 20;

    /** Kosong = awal bulan ini. */
    #[Url(except: '')]
    public string $dari = '';

    /** Kosong = hari ini. */
    #[Url(except: '')]
    public string $sampai = '';

    #[Url(except: '')]
    public string $kelompok = '';

    #[Url(except: '')]
    public string $q = '';

    /** catatan = satu baris per check-in; anak = ringkasan per anak. */
    #[Url(except: 'catatan')]
    public string $tampilan = 'catatan';

    public function setTampilan(string $tampilan): void
    {
        $this->tampilan = $tampilan === 'anak' ? 'anak' : 'catatan';
    }

    public function updated(string $properti): void
    {
        if (in_array($properti, ['dari', 'sampai', 'kelompok', 'q'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilter(): void
    {
        $this->reset('dari', 'sampai', 'kelompok', 'q');
        $this->resetPage();
    }

    /** Catatan kehadiran yang sedang menunggu konfirmasi hapus (null = dialog tertutup). */
    #[Locked]
    public ?int $hapusId = null;

    /** Alasan penghapusan, wajib diisi agar jejak audit jelas. */
    public string $alasan = '';

    public function konfirmasiHapus(int $id): void
    {
        abort_unless(auth()->user()?->can('hapus data kehadiran'), 403);

        $this->resetValidation();
        $this->alasan = '';
        $this->hapusId = Kehadiran::whereKey($id)->value('id');
    }

    public function batalHapus(): void
    {
        $this->reset('hapusId', 'alasan');
        $this->resetValidation();
    }

    public function hapus(): void
    {
        abort_unless(auth()->user()?->can('hapus data kehadiran'), 403);

        $this->validate(
            ['alasan' => ['required', 'string', 'min:5', 'max:255']],
            [],
            ['alasan' => 'alasan penghapusan'],
        );

        $kehadiran = $this->hapusId ? Kehadiran::with('anak:id,nama_lengkap')->find($this->hapusId) : null;
        if (! $kehadiran) {
            $this->batalHapus();
            session()->flash('sukses', 'Data kehadiran sudah tidak ada.');

            return;
        }

        $nama = $kehadiran->anak?->nama_lengkap ?? 'anak';
        $tanggal = $kehadiran->tanggal->format('d/m/Y');

        // Hapus permanen dan catat jejaknya dalam satu transaksi: keduanya berhasil atau tidak sama sekali.
        DB::transaction(function () use ($kehadiran) {
            LogPenghapusanKehadiran::create([
                'kehadiran_id' => $kehadiran->id,
                'anak_id' => $kehadiran->anak_id,
                'tanggal' => $kehadiran->tanggal,
                'data' => $kehadiran->makeHidden('anak')->toArray(),
                'alasan' => trim($this->alasan),
                'dihapus_oleh_id' => auth()->id(),
                'dihapus_pada' => now(),
            ]);

            $kehadiran->delete();
        });

        $this->batalHapus();
        session()->flash('sukses', "Data kehadiran {$nama} tanggal {$tanggal} berhasil dihapus.");
    }

    private function tanggal(string $nilai, CarbonInterface $bawaan): CarbonInterface
    {
        try {
            return $nilai !== '' ? Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay() : $bawaan;
        } catch (\Throwable) {
            return $bawaan;
        }
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('lihat semua laporan'), 403);

        $dari = $this->tanggal($this->dari, today()->startOfMonth());
        $sampai = $this->tanggal($this->sampai, today());

        // Rentang terbalik dibaca apa adanya agar admin langsung melihat bahwa hasilnya kosong, bukan dibetulkan diam-diam.
        $filter = [
            'dari' => $dari,
            'sampai' => $sampai,
            'kelompok' => $this->kelompok !== '' ? (int) $this->kelompok : null,
            'q' => $this->q,
        ];

        $perAnak = $this->tampilan === 'anak';

        // Angka ringkas untuk kartu di atas daftar, memakai filter yang sama dengan rekap().
        $catatan = Kehadiran::rekap($filter)->setEagerLoads([])->reorder()->get(['anak_id', 'check_in_pada', 'check_out_pada']);
        $selesai = $catatan->whereNotNull('check_out_pada');
        $rataDatang = $catatan->avg(fn (Kehadiran $k) => $k->check_in_pada->hour * 60 + $k->check_in_pada->minute);
        $rataDi = $selesai->avg(fn (Kehadiran $k) => $k->check_in_pada->diffInMinutes($k->check_out_pada, true));
        $statistik = [
            'total' => $catatan->count(),
            'anak' => $catatan->pluck('anak_id')->unique()->count(),
            'pulang' => $selesai->count(),
            'masih' => $catatan->count() - $selesai->count(),
            'rata_datang' => $rataDatang === null ? null : sprintf('%02d:%02d', intdiv((int) round($rataDatang), 60), (int) round($rataDatang) % 60),
            'rata_menit_di' => $rataDi === null ? null : (int) round($rataDi),
        ];

        return view('livewire.admin.rekap-kehadiran', [
            'perAnak' => $perAnak,
            'statistik' => $statistik,
            'baris' => $perAnak ? null : Kehadiran::rekap($filter)->paginate(self::PER_HALAMAN),
            'ringkasan' => $perAnak ? Kehadiran::ringkasanPerAnak($filter) : collect(),
            'total' => $perAnak ? Kehadiran::rekap($filter)->count() : null,
            'awal' => $dari,
            'akhir' => $sampai,
            'terbalik' => $dari->gt($sampai),
            'hapusKehadiran' => $this->hapusId ? Kehadiran::with('anak:id,nama_lengkap')->find($this->hapusId) : null,
            'kelompokList' => KelompokUsia::orderBy('usia_min_bulan')->get(['id', 'nama']),
            'adaFilter' => $this->dari !== '' || $this->sampai !== '' || $this->kelompok !== '' || $this->q !== '',
        ]);
    }
}
