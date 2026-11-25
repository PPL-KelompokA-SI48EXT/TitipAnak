<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'laporan_harian_id',
    'path_file',
    'tipe_mime',
    'ukuran_file',
    'keterangan',
])]
class LampiranLaporan extends Model
{
    protected $table = 'lampiran_laporan';

    protected function casts(): array
    {
        return [
            'ukuran_file' => 'integer',
        ];
    }

    public function laporanHarian(): BelongsTo
    {
        return $this->belongsTo(LaporanHarian::class, 'laporan_harian_id');
    }

    public function isUrlEksternal(): bool
    {
        return str_starts_with($this->path_file, 'http://') || str_starts_with($this->path_file, 'https://');
    }

    public function url(): string
    {
        if ($this->isUrlEksternal()) {
            return $this->path_file;
        }

        return Storage::disk('public')->url($this->path_file);
    }
}
