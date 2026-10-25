<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama', 'usia_min_bulan', 'usia_maks_bulan', 'kapasitas', 'deskripsi'])]
class KelompokUsia extends Model
{
    protected $table = 'kelompok_usia';

    public function jadwalShift(): HasMany
    {
        return $this->hasMany(JadwalShift::class);
    }
}
