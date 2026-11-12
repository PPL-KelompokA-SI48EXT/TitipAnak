<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pengguna_id', 'nama_lengkap', 'no_hp', 'alamat', 'pekerjaan', 'kontak_darurat'])]
class ProfilOrangTua extends Model
{
    protected $table = 'profil_orang_tua';

    protected $primaryKey = 'pengguna_id';

    public $incrementing = false;

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }
}
