<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['anak_id', 'nama_lengkap', 'hubungan', 'no_hp', 'path_foto_identitas', 'aktif'])]
class PenjemputTerdaftar extends Model
{
    protected $table = 'penjemput_terdaftar';

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function anak(): BelongsTo
    {
        return $this->belongsTo(Anak::class);
    }
}
