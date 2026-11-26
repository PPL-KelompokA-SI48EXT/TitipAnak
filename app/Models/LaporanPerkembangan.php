<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'anak_id',
    'pengasuh_id',
    'periode_bulan',
    'catatan_motorik',
    'catatan_bahasa',
    'catatan_sosial',
    'catatan_kognitif',
    'catatan_umum',
])]
class LaporanPerkembangan extends Model
{
    protected $table = 'laporan_perkembangan';

    protected function casts(): array
    {
        return [
            'periode_bulan' => 'date',
        ];
    }

    public function anak(): BelongsTo
    {
        return $this->belongsTo(Anak::class)->withTrashed();
    }

    public function pengasuh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengasuh_id');
    }

    public function scopePadaPeriode(Builder $query, CarbonInterface $periode): void
    {
        $query->whereDate('periode_bulan', $periode->copy()->startOfMonth()->toDateString());
    }
}