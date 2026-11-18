<?php

namespace App\Livewire\Admin;

use App\Models\Pendaftaran;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * PBI-11e: Admin melihat daftar pendaftaran yang masuk agar bisa memeriksanya satu per satu.
 *
 * "Masuk" = sudah diajukan orang tua (Menunggu Verifikasi) atau sudah diproses (Disetujui / Ditolak).
 * Draft tidak ditampilkan karena datanya belum lengkap dan belum diteruskan ke Admin (lihat PBI-11d).
 */
#[Title('Pendaftaran')]
class PendaftaranIndex extends Component
{
    /** Status yang tampil di halaman Admin, sesuai urutan tab. */
    public const STATUS_MASUK = ['menunggu_verifikasi', 'disetujui', 'ditolak'];

    /** Tab status: '' = semua pendaftaran yang masuk. */
    #[Url(except: '')]
    public string $status = '';

    /** Kata kunci: nama anak atau nama orang tua. */
    #[Url(except: '')]
    public string $q = '';

    public function pilihStatus(string $status): void
    {
        $this->status = in_array($status, self::STATUS_MASUK, true) ? $status : '';
    }

    public function resetFilter(): void
    {
        $this->reset('status', 'q');
    }

    /** Query dasar: hanya pendaftaran yang sudah masuk ke Admin. */
    protected function pendaftaranMasuk(): Builder
    {
        return Pendaftaran::query()->whereIn('status', self::STATUS_MASUK);
    }

    public function render(): View
    {
        $status = in_array($this->status, self::STATUS_MASUK, true) ? $this->status : '';
        $kataKunci = trim($this->q);

        $pendaftaran = $this->pendaftaranMasuk()
            ->with([
                'orangTua:id,nama,email',
                'paketLayanan:id,nama',
                'anak:id,pendaftaran_id,nama_lengkap,tanggal_lahir,jenis_kelamin',
            ])
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($kataKunci !== '', fn (Builder $query) => $query->where(fn (Builder $cari) => $cari
                ->whereHas('anak', fn (Builder $anak) => $anak->where('nama_lengkap', 'like', "%{$kataKunci}%"))
                ->orWhereHas('orangTua', fn (Builder $ortu) => $ortu->where('nama', 'like', "%{$kataKunci}%"))
            ))
            // Yang menunggu diperiksa tampil paling atas, urut dari yang paling lama diajukan (antre).
            // Sisanya (sudah diproses) urut dari yang terbaru.
            ->orderByRaw("CASE WHEN status = 'menunggu_verifikasi' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status = 'menunggu_verifikasi' THEN diajukan_pada END ASC")
            ->orderByDesc('diajukan_pada')
            ->orderByDesc('id')
            ->get();

        $jumlah = $this->pendaftaranMasuk()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return view('livewire.admin.pendaftaran-index', [
            'pendaftaran' => $pendaftaran,
            'statusAktif' => $status,
            'jumlah' => $jumlah,
            'jumlahSemua' => (int) $jumlah->sum(),
        ]);
    }
}
