<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Catatan kesehatan anak (satu per anak). Salah satu syarat kelengkapan pendaftaran. */
#[Fillable(['anak_id', 'riwayat_medis', 'alergi', 'pantangan_makanan', 'catatan'])]
class CatatanKesehatan extends Model
{
    protected $table = 'catatan_kesehatan';

    public function anak(): BelongsTo
    {
        return $this->belongsTo(Anak::class);
    }
}
