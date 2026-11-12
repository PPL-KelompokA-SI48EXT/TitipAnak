<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'anak_id', 'tanggal', 'check_in_pada', 'kondisi_check_in', 'check_in_oleh_id',
    'check_out_pada', 'check_out_oleh_id', 'penjemput_terdaftar_id', 'izin_penjemputan_id', 'nama_penjemput',
])]
class Kehadiran extends Model
{
    protected $table = 'kehadiran';

    public const STATUS_HADIR = 'hadir';

    public const STATUS_PULANG = 'pulang';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'check_in_pada' => 'datetime',
            'check_out_pada' => 'datetime',
        ];
    }

    public function anak(): BelongsTo
    {
        return $this->belongsTo(Anak::class)->withTrashed();
    }

    public function checkInOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_in_oleh_id');
    }

    public function checkOutOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'check_out_oleh_id');
    }

    public function penjemputTerdaftar(): BelongsTo
    {
        return $this->belongsTo(PenjemputTerdaftar::class);
    }

    public function izinPenjemputan(): BelongsTo
    {
        return $this->belongsTo(IzinPenjemputan::class);
    }

    public function scopePadaTanggal(Builder $query, CarbonInterface $tanggal): void
    {
        $query->whereDate('tanggal', $tanggal->toDateString());
    }

    /** Kehadiran dalam rentang tanggal (inklusif). Sisi kosong berarti tanpa batas. */
    public function scopeAntara(Builder $query, ?CarbonInterface $dari, ?CarbonInterface $sampai): void
    {
        $query
            ->when($dari, fn (Builder $q) => $q->whereDate('tanggal', '>=', $dari->toDateString()))
            ->when($sampai, fn (Builder $q) => $q->whereDate('tanggal', '<=', $sampai->toDateString()));
    }

    /** Kehadiran anak yang tergabung di kelompok usia tertentu. */
    public function scopeKelompokUsia(Builder $query, int $kelompokId): void
    {
        $query->whereHas('anak', fn (Builder $a) => $a->where('kelompok_usia_id', $kelompokId));
    }

    /** Kehadiran anak yang namanya memuat kata kunci. */
    public function scopeNamaAnak(Builder $query, string $kata): void
    {
        $query->whereHas('anak', fn (Builder $a) => $a->where('nama_lengkap', 'like', '%'.trim($kata).'%'));
    }

    /**
     * Query rekap kehadiran: filter tanggal, kelompok usia, dan nama anak, terbaru dulu.
     *
     * @param  array{dari?: ?CarbonInterface, sampai?: ?CarbonInterface, kelompok?: ?int, q?: ?string}  $filter
     */
    public static function rekap(array $filter = []): Builder
    {
        return static::query()
            ->antara($filter['dari'] ?? null, $filter['sampai'] ?? null)
            ->when($filter['kelompok'] ?? null, fn (Builder $q, int $k) => $q->kelompokUsia($k))
            ->when(filled($filter['q'] ?? null), fn (Builder $q) => $q->namaAnak($filter['q']))
            ->with(['anak.kelompokUsia:id,nama', 'penjemputTerdaftar:id,nama_lengkap,hubungan'])
            ->orderByDesc('tanggal')
            ->orderBy('check_in_pada')
            ->orderBy('id');
    }

    /**
     * Ringkasan kehadiran per anak pada filter yang sama dengan rekap(): jumlah hari hadir, rata-rata jam datang,
     * rata-rata lama di tempat (hanya hari yang sudah check-out), dan jumlah hari yang belum check-out.
     * Diurutkan dari yang paling sering hadir, lalu nama.
     *
     * @param  array{dari?: ?CarbonInterface, sampai?: ?CarbonInterface, kelompok?: ?int, q?: ?string}  $filter
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function ringkasanPerAnak(array $filter = []): \Illuminate\Support\Collection
    {
        return static::rekap($filter)->get()
            ->groupBy('anak_id')
            ->map(function ($catatan) {
                $anak = $catatan->first()->anak;
                $selesai = $catatan->filter(fn (Kehadiran $k) => $k->check_out_pada !== null);
                $rataMenitDatang = $catatan->avg(fn (Kehadiran $k) => $k->check_in_pada->hour * 60 + $k->check_in_pada->minute);
                $rataMenitDi = $selesai->avg(fn (Kehadiran $k) => $k->check_in_pada->diffInMinutes($k->check_out_pada, true));

                return [
                    'anak_id' => $anak?->id,
                    'nama' => $anak?->nama_lengkap ?? '—',
                    'kelompok' => $anak?->kelompokUsia?->nama,
                    'hari_hadir' => $catatan->count(),
                    'belum_pulang' => $catatan->count() - $selesai->count(),
                    'rata_jam_datang' => $rataMenitDatang === null ? null : sprintf('%02d:%02d', intdiv((int) round($rataMenitDatang), 60), (int) round($rataMenitDatang) % 60),
                    'rata_menit_di_tempat' => $rataMenitDi === null ? null : (int) round($rataMenitDi),
                ];
            })
            ->sortBy([['hari_hadir', 'desc'], ['nama', 'asc']])
            ->values();
    }

    /** Anak sudah check-in tetapi belum check-out. */
    public function scopeMasihDiTempat(Builder $query): void
    {
        $query->whereNull('check_out_pada');
    }

    /** hadir = sudah check-in dan belum dijemput; pulang = sudah check-out. */
    public function status(): string
    {
        return $this->check_out_pada ? self::STATUS_PULANG : self::STATUS_HADIR;
    }

    public function sudahPulang(): bool
    {
        return $this->check_out_pada !== null;
    }
}
