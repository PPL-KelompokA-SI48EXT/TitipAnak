<?php

namespace Database\Seeders\Concerns;

/**
 * Foto contoh untuk seeder: gambar karakter Kimetsu no Yaiba dari CDN AniList.
 * Hanya untuk data pengembangan, bukan untuk produksi.
 */
class FotoContoh
{
    private const URLS = [
        'https://s4.anilist.co/file/anilistcdn/character/large/b126071-BTNEc1nRIv68.png', // Tanjirou Kamado
        'https://s4.anilist.co/file/anilistcdn/character/large/b127518-NRlq1CQ1v1ro.png', // Nezuko Kamado
        'https://s4.anilist.co/file/anilistcdn/character/large/n129130-SJC0Kn1DU39E.jpg', // Inosuke Hashibira
        'https://s4.anilist.co/file/anilistcdn/character/large/b129131-FZrQ7lSlxmEr.png', // Zenitsu Agatsuma
        'https://s4.anilist.co/file/anilistcdn/character/large/b130050-qsLThJs5VIbz.png', // Giyuu Tomioka
        'https://s4.anilist.co/file/anilistcdn/character/large/b129133-VlTPowwt68rJ.png', // Kyoujurou Rengoku
        'https://s4.anilist.co/file/anilistcdn/character/large/b136070-MC9LLxJsHyHE.png', // Shinobu Kochou
        'https://s4.anilist.co/file/anilistcdn/character/large/b136071-99Kexnnn2PiV.png', // Tengen Uzui
        'https://s4.anilist.co/file/anilistcdn/character/large/b136072-xVwyRUKdpybi.png', // Mitsuri Kanroji
        'https://s4.anilist.co/file/anilistcdn/character/large/b139038-OcOoDUyLEsRn.png', // Kanao Tsuyuri
        'https://s4.anilist.co/file/anilistcdn/character/large/b136069-6PLglx4tETUX.png', // Muichirou Tokitou
        'https://s4.anilist.co/file/anilistcdn/character/large/b137777-kGViiNyx0wa7.jpg', // Obanai Iguro
        'https://s4.anilist.co/file/anilistcdn/character/large/b137774-O1iYrnGLB71l.png', // Sanemi Shinazugawa
        'https://s4.anilist.co/file/anilistcdn/character/large/b137809-6Tkle99lCBl8.png', // Sabito
        'https://s4.anilist.co/file/anilistcdn/character/large/b137776-E0QuFD7y19OQ.jpg', // Genya Shinazugawa
        'https://s4.anilist.co/file/anilistcdn/character/large/b137778-H4Uzb9cSCvZ6.jpg', // Gyoumei Himejima
        'https://s4.anilist.co/file/anilistcdn/character/large/b138847-rOwIPLKaR5Ux.jpg', // Tamayo
        'https://s4.anilist.co/file/anilistcdn/character/large/b138990-E5kiUoLigpyX.jpg', // Hotaru Haganezuka
        'https://s4.anilist.co/file/anilistcdn/character/large/b137773-N4O52f73dJKZ.png', // Sakonji Urokodaki
        'https://s4.anilist.co/file/anilistcdn/character/large/b138846-i8frJEl8baly.jpg', // Yushirou
        'https://s4.anilist.co/file/anilistcdn/character/large/b137808-4yA8XQUDrAho.png', // Kagaya Ubuyashiki
    ];

    /**
     * URL foto ke-$n (berputar kalau $n melebihi jumlah foto).
     */
    public static function url(int $n): string
    {
        return self::URLS[$n % count(self::URLS)];
    }
}
