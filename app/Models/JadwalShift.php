<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pengasuh_id', 'kelompok_usia_id', 'hari', 'jam_mulai', 'jam_selesai'])]
class JadwalShift extends Model
{
    protected $table = 'jadwal_shift';

    /** Pola mingguan: 1 = Senin sampai 7 = Minggu. */
    public const HARI = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    /** Shift milik satu pengasuh. */
    public function scopeMilik(Builder $query, User $pengasuh): void
    {
        $query->where('pengasuh_id', $pengasuh->id);
    }

    /** Shift yang berlaku pada tanggal tertentu (pola mingguan dipetakan lewat hari ISO). */
    public function scopePadaTanggal(Builder $query, CarbonInterface $tanggal): void
    {
        $query->where('hari', $tanggal->dayOfWeekIso);
    }

    /**
     * Shift pengasuh pada tanggal itu beserta kelompok usia dan anak aktif yang menjadi tanggung jawabnya.
     *
     * @return Collection<int, JadwalShift>
     */
    public static function tanggungJawab(User $pengasuh, CarbonInterface $tanggal): Collection
    {
        return static::query()
            ->milik($pengasuh)
            ->padaTanggal($tanggal)
            ->with(['kelompokUsia', 'kelompokUsia.anak' => fn ($q) => $q->orderBy('nama_lengkap')])
            ->orderBy('jam_mulai')
            ->orderBy('id')
            ->get();
    }

    /**
     * Pengasuh yang bertugas pada tanggal itu di kelompok-kelompok usia tertentu, urut jam,
     * dikelompokkan per kelompok usia. Hanya nama pengasuh yang dimuat (dipakai untuk tampilan orang tua).
     *
     * @param  iterable<int>  $kelompokIds
     * @return Collection<int, Collection<int, JadwalShift>>
     */
    public static function bertugasUntukKelompok(iterable $kelompokIds, CarbonInterface $tanggal): Collection
    {
        $ids = collect($kelompokIds)->unique()->values();

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return static::query()
            ->whereIn('kelompok_usia_id', $ids)
            ->padaTanggal($tanggal)
            ->with('pengasuh:id,nama')
            ->orderBy('jam_mulai')
            ->orderBy('id')
            ->get()
            ->groupBy('kelompok_usia_id');
    }

    /**
     * Seluruh pola shift mingguan pengasuh, urut hari lalu jam.
     *
     * @return Collection<int, JadwalShift>
     */
    public static function polaMingguan(User $pengasuh): Collection
    {
        return static::query()
            ->milik($pengasuh)
            ->with('kelompokUsia:id,nama')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();
    }

    public function pengasuh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengasuh_id');
    }

    public function kelompokUsia(): BelongsTo
    {
        return $this->belongsTo(KelompokUsia::class);
    }
}
