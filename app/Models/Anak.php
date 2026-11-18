<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['pendaftaran_id', 'orang_tua_id', 'kelompok_usia_id', 'nama_lengkap', 'tanggal_lahir', 'jenis_kelamin', 'status'])]
class Anak extends Model
{
    use SoftDeletes;

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
        return ['tanggal_lahir' => 'date'];
    }

    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orang_tua_id');
    }

    public function kelompokUsia(): BelongsTo
    {
        return $this->belongsTo(KelompokUsia::class);
    }
}
