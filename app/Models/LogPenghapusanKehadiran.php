<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kehadiran_id', 'anak_id', 'tanggal', 'data', 'alasan', 'dihapus_oleh_id', 'dihapus_pada'])]
class LogPenghapusanKehadiran extends Model
{
    protected $table = 'log_penghapusan_kehadiran';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'data' => 'array',
            'dihapus_pada' => 'datetime',
        ];
    }

    public function anak(): BelongsTo
    {
        return $this->belongsTo(Anak::class)->withTrashed();
    }

    public function dihapusOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dihapus_oleh_id');
    }
}
