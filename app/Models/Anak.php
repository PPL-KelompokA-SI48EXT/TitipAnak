<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orang_tua_id');
    }

    /** Anak aktif di kelompok usia yang dipegang pengasuh pada tanggal itu (berdasarkan shift hari itu). */
    public function scopeDiasuhOleh(Builder $query, User $pengasuh, CarbonInterface $tanggal): void
    {
        $query->where('status', 'aktif')->whereIn(
            'kelompok_usia_id',
            JadwalShift::query()->milik($pengasuh)->padaTanggal($tanggal)->select('kelompok_usia_id')
        );
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class);
    }

    public function penjemputTerdaftar(): HasMany
    {
        return $this->hasMany(PenjemputTerdaftar::class);
    }

    public function izinPenjemputan(): HasMany
    {
        return $this->hasMany(IzinPenjemputan::class);
    }

    public function kelompokUsia(): BelongsTo
    {
        return $this->belongsTo(KelompokUsia::class);
    }
}
