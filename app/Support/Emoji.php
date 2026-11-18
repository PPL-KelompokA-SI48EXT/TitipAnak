<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Mengubah karakter emoji menjadi gambar OpenMoji (public/images/openmoji/{kode}.svg) supaya tampilannya
 * sama di semua perangkat. Bila gambarnya tidak ada, emoji ditampilkan sebagai teks biasa.
 */
class Emoji
{
    /** @var array<string, bool> */
    private static array $ada = [];

    /** Kode titik Unicode tanpa penanda variasi FE0F, mis. "🍽️" menjadi "1F37D". */
    public static function kode(string $emoji): string
    {
        $titik = [];
        foreach (mb_str_split($emoji) as $huruf) {
            $nilai = mb_ord($huruf);
            if ($nilai !== 0xFE0F) {
                $titik[] = strtoupper(dechex($nilai));
            }
        }

        return implode('-', $titik);
    }

    public static function tersedia(string $kode): bool
    {
        return self::$ada[$kode] ??= $kode !== '' && is_file(public_path("images/openmoji/{$kode}.svg"));
    }

    /** Gambar ikon (dekoratif, aria-hidden) atau emoji teks bila gambarnya belum ada. */
    public static function html(string $emoji, string $kelas = ''): HtmlString
    {
        $kode = self::kode($emoji);
        $kelas = e(trim('inline-block size-[1.2em] shrink-0 align-[-0.2em] '.$kelas));

        if (! self::tersedia($kode)) {
            return new HtmlString('<span aria-hidden="true">'.e($emoji).'</span>');
        }

        return new HtmlString('<img src="'.e(asset("images/openmoji/{$kode}.svg")).'" alt="" aria-hidden="true" class="'.$kelas.'" loading="lazy" decoding="async">');
    }
}
