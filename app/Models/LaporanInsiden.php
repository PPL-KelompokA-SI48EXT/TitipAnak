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
    'waktu_kejadian',
    'judul',
    'deskripsi',
    'tindakan',
])]
class LaporanInsiden extends Model
{
    protected $table = 'laporan_insiden';

    protected function casts(): array
    {
        return [
            'waktu_kejadian' => 'datetime',
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

    public function scopePadaTanggal(Builder $query, CarbonInterface $tanggal): void
    {
        $query->whereDate('waktu_kejadian', $tanggal->toDateString());
    }
}