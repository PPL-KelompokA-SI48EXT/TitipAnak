<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'anak_id', 'diajukan_oleh_id', 'nama_penjemput', 'path_foto_identitas', 'alasan', 'tanggal_jemput',
    'status', 'divalidasi_oleh_id', 'divalidasi_pada',
])]
class IzinPenjemputan extends Model
{
    protected $table = 'izin_penjemputan';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected function casts(): array
    {
        return [
            'tanggal_jemput' => 'date',
            'divalidasi_pada' => 'datetime',
        ];
    }

    public function anak(): BelongsTo
    {
        return $this->belongsTo(Anak::class);
    }

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh_id');
    }

    public function divalidasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh_id');
    }

    /** Check-out yang memakai izin ini (kosong bila belum dipakai). */
    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class, 'izin_penjemputan_id');
    }

    public function scopeDisetujui(Builder $query): void
    {
        $query->where('status', self::STATUS_DISETUJUI);
    }

    public function scopePadaTanggal(Builder $query, CarbonInterface $tanggal): void
    {
        $query->whereDate('tanggal_jemput', $tanggal->toDateString());
    }
}
