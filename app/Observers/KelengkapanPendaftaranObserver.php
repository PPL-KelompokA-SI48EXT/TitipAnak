<?php

namespace App\Observers;

use App\Models\Anak;
use App\Models\CatatanKesehatan;
use App\Models\Pendaftaran;
use App\Models\PenjemputTerdaftar;

/**
 * PBI-11d: setiap kali data anak, catatan kesehatan, atau penjemput terdaftar disimpan,
 * cek apakah pendaftaran Draft-nya sudah lengkap. Jika ya, status otomatis menjadi
 * Menunggu Verifikasi sehingga Admin bisa memprosesnya.
 *
 * Didaftarkan di AppServiceProvider supaya model milik PBI lain tidak perlu diubah.
 */
class KelengkapanPendaftaranObserver
{
    public function saved(Anak|CatatanKesehatan|PenjemputTerdaftar $model): void
    {
        $pendaftaranId = $model instanceof Anak
            ? $model->pendaftaran_id
            : Anak::whereKey($model->anak_id)->value('pendaftaran_id');

        if ($pendaftaranId === null) {
            return;
        }

        Pendaftaran::query()
            ->where('status', 'draft')
            ->find($pendaftaranId)
            ?->ajukanJikaLengkap();
    }
}
