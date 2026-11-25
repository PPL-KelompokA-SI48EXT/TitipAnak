<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'anak_id',
    'pengasuh_id',
    'tanggal',
    'status_makan',
    'catatan_makan',
    'durasi_tidur_menit',
    'catatan_buang_air',
    'suasana_hati',
    'catatan_aktivitas',
    'catatan_umum',
])]
class LaporanHarian extends Model
{
    protected $table = 'laporan_harian';

    public const STATUS_MAKAN = [
        'baik' => 'Habis / Baik',
        'sebagian' => 'Sebagian',
        'tidak_makan' => 'Tidak Makan',
    ];

    public const SUASANA_HATI = [
        'ceria' => 'Ceria',
        'tenang' => 'Tenang',
        'bersemangat' => 'Bersemangat',
        'mengantuk' => 'Mengantuk',
        'rewel' => 'Rewel',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'durasi_tidur_menit' => 'integer',
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

    public function lampiran(): HasMany
    {
        return $this->hasMany(LampiranLaporan::class, 'laporan_harian_id');
    }

    public function scopePadaTanggal(Builder $query, CarbonInterface $tanggal): void
    {
        $query->whereDate('tanggal', $tanggal->toDateString());
    }

    public function labelStatusMakan(): string
    {
        return self::STATUS_MAKAN[$this->status_makan] ?? ($this->status_makan ? ucfirst($this->status_makan) : '—');
    }

    public function labelSuasanaHati(): string
    {
        return self::SUASANA_HATI[$this->suasana_hati] ?? ($this->suasana_hati ? ucfirst($this->suasana_hati) : '—');
    }
}