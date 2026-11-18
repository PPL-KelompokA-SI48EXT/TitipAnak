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
     * PBI-11d: syarat kelengkapan sebelum pendaftaran diajukan ke Admin.
     * Dibaca langsung dari database (bukan relasi yang sudah dimuat) agar selalu terkini.
     *
     * @return array<'anak'|'kesehatan'|'penjemput', array{label: string, lengkap: bool}>
     */
    public function kelengkapan(): array
    {
        $anak = $this->anak()->first();

        $adaAnak = $anak !== null
            && filled($anak->nama_lengkap)
            && $anak->tanggal_lahir !== null
            && filled($anak->jenis_kelamin);

        return [
            'anak' => [
                'label' => 'Data anak',
                'lengkap' => $adaAnak,
            ],
            'kesehatan' => [
                'label' => 'Catatan kesehatan',
                'lengkap' => $anak !== null && CatatanKesehatan::where('anak_id', $anak->id)->exists(),
            ],
            'penjemput' => [
                'label' => 'Penjemput terdaftar',
                'lengkap' => $anak !== null && PenjemputTerdaftar::where('anak_id', $anak->id)->where('aktif', true)->exists(),
            ],
        ];
    }

    /** PBI-11d: data anak, catatan kesehatan, dan minimal satu penjemput aktif sudah diisi. */
    public function dataLengkap(): bool
    {
        return collect($this->kelengkapan())->every(fn (array $syarat) => $syarat['lengkap']);
    }

    /**
     * PBI-11d: ubah Draft menjadi Menunggu Verifikasi begitu datanya lengkap, agar Admin bisa memprosesnya.
     * Selain Draft tidak disentuh. Update bersyarat status = draft supaya aman bila status
     * sudah berubah (atau pendaftaran dihapus) sejak model dimuat.
     *
     * @return bool true jika status berubah.
     */
    public function ajukanJikaLengkap(): bool
    {
        if ($this->status !== 'draft' || ! $this->dataLengkap()) {
            return false;
        }

        $diajukanPada = now();

        $berubah = static::query()
            ->whereKey($this->getKey())
            ->where('status', 'draft')
            ->update([
                'status' => 'menunggu_verifikasi',
                'diajukan_pada' => $diajukanPada,
                'updated_at' => $diajukanPada,
            ]) > 0;

        if ($berubah) {
            $this->forceFill(['status' => 'menunggu_verifikasi', 'diajukan_pada' => $diajukanPada, 'updated_at' => $diajukanPada])
                ->syncOriginal();
        }

        return $berubah;
    }

    /** PBI-11f: Admin hanya bisa menyetujui pendaftaran yang sedang Menunggu Verifikasi. */
    public function bisaDisetujui(): bool
    {
        return $this->status === 'menunggu_verifikasi';
    }

    /**
     * PBI-11f: Admin menyetujui pendaftaran sekaligus menetapkan kelompok usia anak,
     * sehingga anak berstatus aktif.
     *
     * Satu database transaction (NFR-06): status pendaftaran dan data anak berubah bersama,
     * atau tidak sama sekali. Update bersyarat status = menunggu_verifikasi supaya pendaftaran
     * yang sudah diproses (mis. dibuka di dua tab) tidak disetujui dua kali.
     *
     * @return bool true jika pendaftaran berhasil disetujui.
     */
    public function setujui(KelompokUsia $kelompokUsia, User $admin): bool
    {
        $disetujui = DB::transaction(function () use ($kelompokUsia, $admin) {
            $anak = $this->anak()->lockForUpdate()->first();

            if ($anak === null) {
                return false;
            }

            $waktu = now();

            $berubah = static::query()
                ->whereKey($this->getKey())
                ->where('status', 'menunggu_verifikasi')
                ->update([
                    'status' => 'disetujui',
                    'diverifikasi_oleh_id' => $admin->id,
                    'diverifikasi_pada' => $waktu,
                    'catatan_penolakan' => null,
                    'updated_at' => $waktu,
                ]) > 0;

            if (! $berubah) {
                return false;
            }

            $anak->update([
                'kelompok_usia_id' => $kelompokUsia->id,
                'status' => 'aktif',
            ]);

            return true;
        });

        if ($disetujui) {
            $this->refresh();
        }

        return $disetujui;
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
