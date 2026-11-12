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
