<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Mengubah path foto identitas menjadi sumber gambar yang bisa ditampilkan.
 * Path lokal dibaca dari disk "local" (privat) dan dikirim sebagai data URI.
 * Alamat http(s) (foto contoh dari seeder) dipakai apa adanya.
 */
class FotoIdentitas
{
    public static function sumber(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return null;
        }

        return 'data:'.$disk->mimeType($path).';base64,'.base64_encode($disk->get($path));
    }

    public static function hapus(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('local')->delete($path);
        }
    }
}
