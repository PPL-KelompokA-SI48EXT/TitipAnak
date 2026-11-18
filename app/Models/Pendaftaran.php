<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    /** Urutan tahapan yang dilalui pendaftaran. Ditolak menggantikan tahap terakhir. */
    public const TAHAPAN = ['draft', 'menunggu_verifikasi', 'disetujui'];

    protected function casts(): array
    {
        return [
            'diajukan_pada' => 'datetime',
            'diverifikasi_pada' => 'datetime',
        ];
    }

    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orang_tua_id');
    }

    public function paketLayanan(): BelongsTo
    {
        return $this->belongsTo(PaketLayanan::class);
    }

    public function anak(): HasOne
    {
        return $this->hasOne(Anak::class);
    }

    public function labelStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** PBI-11c: orang tua hanya boleh menghapus pendaftaran yang masih Draft. */
    public function bisaDihapus(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * PBI-11c: batalkan pendaftaran Draft beserta data anak yang sudah diisi.
     * Soft delete, jadi relasi foreign key tetap utuh dan data masih bisa dipulihkan.
     */
    public function hapusDraft(): void
    {
        DB::transaction(function () {
            $this->anak()->delete();
            $this->delete();
        });
    }

    /**
     * PBI-11b: tahapan pendaftaran untuk ditampilkan ke orang tua.
     *
     * @return list<array{status: string, label: string, keadaan: string, waktu: ?Carbon}>
     */
    public function tahapan(): array
    {
        $posisi = $this->status === 'ditolak' ? 2 : array_search($this->status, self::TAHAPAN, true);
        $waktu = [$this->created_at, $this->diajukan_pada, $this->diverifikasi_pada];

        return array_map(function (int $i) use ($posisi, $waktu) {
            $status = $i === 2 && $this->status === 'ditolak' ? 'ditolak' : self::TAHAPAN[$i];

            return [
                'status' => $status,
                'label' => self::STATUS[$status],
                // selesai = sudah dilewati, aktif = posisi sekarang, belum = belum sampai
                'keadaan' => match (true) {
                    $posisi === false => 'belum',
                    $i < $posisi => 'selesai',
                    $i === $posisi => in_array($status, ['disetujui', 'ditolak'], true) ? 'selesai' : 'aktif',
                    default => 'belum',
                },
                'waktu' => $posisi !== false && $i <= $posisi ? $waktu[$i] : null,
            ];
        }, array_keys(self::TAHAPAN));
    }
}
