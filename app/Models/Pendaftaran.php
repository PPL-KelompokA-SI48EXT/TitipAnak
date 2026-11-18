<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['orang_tua_id', 'paket_layanan_id', 'status', 'diajukan_pada', 'diverifikasi_oleh_id', 'diverifikasi_pada', 'catatan_penolakan'])]
class Pendaftaran extends Model
{
    use SoftDeletes;

    protected $table = 'pendaftaran';

    /** Status pendaftaran dan label yang ditampilkan ke pengguna. */
    public const STATUS = [
        'draft' => 'Draft',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
    ];

    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orang_tua_id');
    }

    public function paketLayanan(): BelongsTo
    {
        return $this->belongsTo(PaketLayanan::class);
    }

    public function labelStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}
