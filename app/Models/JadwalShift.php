<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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

    public function pengasuh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengasuh_id');
    }

    public function kelompokUsia(): BelongsTo
    {
        return $this->belongsTo(KelompokUsia::class);
    }
}
