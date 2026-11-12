<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kelompok_usia_id', 'nama_kegiatan', 'jenis_kegiatan', 'hari', 'jam_mulai', 'jam_selesai', 'catatan'])]
class JadwalKegiatan extends Model
{
    protected $table = 'jadwal_kegiatan';

    /** Jenis kegiatan yang dikenal sistem. */
    public const JENIS = [
        'makan' => 'Makan',
        'bermain' => 'Bermain',
        'belajar' => 'Belajar',
        'tidur' => 'Tidur',
        'lainnya' => 'Lainnya',
    ];

    public function kelompokUsia(): BelongsTo
    {
        return $this->belongsTo(KelompokUsia::class);
    }

    /** Kegiatan yang berlaku pada tanggal itu: khusus hari tersebut atau berlaku setiap hari (hari null). */
    public function scopePadaTanggal(Builder $query, CarbonInterface $tanggal): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('hari')->orWhere('hari', $tanggal->dayOfWeekIso));
    }

    /** Kegiatan yang jamnya bertabrakan dengan rentang [$mulai, $selesai) pada hari yang beririsan. */
    public function scopeBertabrakan(Builder $query, int $kelompokId, ?int $hari, string $mulai, string $selesai): void
    {
        // Kolom time disimpan H:i:s; samakan format agar perbandingan teks akurat.
        $mulai = strlen($mulai) === 5 ? $mulai.':00' : $mulai;
        $selesai = strlen($selesai) === 5 ? $selesai.':00' : $selesai;

        $query->where('kelompok_usia_id', $kelompokId)
            ->when($hari !== null, fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('hari')->orWhere('hari', $hari)))
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai);
    }

    public function labelHari(): string
    {
        return $this->hari === null ? 'Setiap hari' : (JadwalShift::HARI[$this->hari] ?? (string) $this->hari);
    }

    public function labelJenis(): string
    {
        return self::JENIS[$this->jenis_kegiatan] ?? ucfirst($this->jenis_kegiatan);
    }
}
