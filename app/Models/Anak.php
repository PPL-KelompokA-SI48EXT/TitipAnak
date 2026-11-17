<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pendaftaran_id', 'orang_tua_id', 'kelompok_usia_id', 'nama_lengkap', 'tanggal_lahir', 'jenis_kelamin', 'status'])]
class Anak extends Model
{
    protected $table = 'anak';

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function penjemputTerdaftar(): HasMany
    {
        return $this->hasMany(PenjemputTerdaftar::class);
    }
}
