<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TitipAnak · Layanan Penitipan Anak yang Terpantau</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <meta name="description" content="TitipAnak membantu orang tua menitipkan anak dengan tenang: pilih paket layanan, daftarkan penjemput terpercaya, dan ketahui anak selalu didampingi pengasuh.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $masuk = auth()->check();
    // Aplikasi belum punya halaman registrasi: tamu diarahkan ke login, orang tua yang sudah masuk langsung ke halaman paket.
    $urlDaftar = ! $masuk
        ? route('login')
        : (auth()->user()->can('kelola pendaftaran sendiri') ? route('orang-tua.paket-layanan.index') : route('dashboard'));
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
    $tombol = 'inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow';
    $tombolBulat = 'grid size-11 place-items-center rounded-ta border-[1.5px] border-ink bg-paper text-lg font-bold transition hover:bg-yellow focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow';

    $usia = function ($k) {
        $fmt = fn (int $bulan) => $bulan < 24
            ? $bulan.' bln'
            : rtrim(rtrim(number_format($bulan / 12, 1, ',', ''), '0'), ',').' thn';

        return $fmt($k->usia_min_bulan).' – '.$fmt($k->usia_maks_bulan);
    };

    // Semua foto ada di public/images/landing (foto stok dari Pexels). Berkas yang hilang dilewati.
    $ada = fn ($berkas) => file_exists(public_path('images/landing/'.$berkas));

    // Carousel galeri (hanya foto landscape): [berkas, alt, judul, keterangan, posisi crop]
    $foto = collect([
        ['daycare-anak.jpg', 'Seorang pengasuh bermain bola bersama tiga anak yang duduk di karpet ruang kelas yang cerah', 'Belajar sambil bermain', 'Kegiatan kelompok kecil yang dipandu pengasuh sesuai jadwal.', 'object-center'],
        ['pengasuh-bermain.jpg', 'Dua pengasuh duduk bermain bersama anak-anak di atas karpet bermain', 'Pengasuh yang selalu dekat', 'Anak didampingi dari datang sampai dijemput.', 'object-center'],
        ['pexels-8441840.jpg', 'Dua guru berlutut memeluk sekelompok anak di ruang kelas', 'Disambut dengan pelukan', 'Setiap pagi anak disambut hangat oleh pengasuhnya.', 'object-center'],
        ['pexels-8466764.jpg', 'Sekelompok anak makan buah bersama di meja berwarna biru', 'Makan bersama', 'Waktu makan dan istirahat teratur sesuai jadwal kegiatan.', 'object-center'],
        ['pexels-8535183.jpg', 'Seorang pengasuh bermain bersama anak-anak di rumah-rumahan kardus', 'Sudut bermain yang nyaman', 'Anak punya tempat bermain yang hangat dan menyenangkan.', 'object-center'],
        ['pexels-14025659.jpg', 'Anak-anak membaca buku cerita bergambar bersama di luar ruangan', 'Gemar membaca sejak kecil', 'Mengenal buku dan cerita lewat kegiatan bersama.', 'object-center'],
    ])->filter(fn ($f) => $ada($f[0]))->values();

    // Pita foto yang bergerak otomatis: [berkas, alt, posisi crop]
    $pita = collect([
        ['pexels-8422169.jpg', 'Anak-anak bermain bola warna-warni bersama pengasuh', 'object-center'],
        ['pexels-8535198.jpg', 'Anak-anak menggambar di meja bersama guru', 'object-center'],
        ['anak-bermain.jpg', 'Anak-anak bermain balok, bola, dan mainan kayu di atas karpet', 'object-[50%_82%]'],
        ['pexels-8617523.jpg', 'Anak-anak perempuan makan siang bersama', 'object-center'],
        ['pexels-8422164.jpg', 'Anak-anak duduk di kursi mendengarkan guru di depan kelas', 'object-center'],
        ['pexels-35136501.jpg', 'Anak bermain di perosotan warna-warni di taman bermain', 'object-[50%_8%]'],
        ['pexels-8535598.jpg', 'Seorang guru berada di depan dinding penuh gambar karya anak', 'object-center'],
    ])->filter(fn ($f) => $ada($f[0]))->values();

    // Foto kecil yang dipakai di bagian-bagian lain: nama => [berkas, alt]
    $gambar = [
        'daftar' => ['pexels-8612970.jpg', 'Seorang anak bermain dengan mainan balok di ruang bermain'],
        'jemput' => ['pexels-8422191.jpg', 'Seorang pengasuh duduk menemani anak bermain di lantai'],
        'jadwal' => ['pexels-8422132.jpg', 'Anak-anak duduk rapi di kursi putih saat kegiatan kelas'],
        'aman' => ['pexels-8422262.jpg', 'Seorang pengasuh bermain dekat dengan dua anak di lantai'],
        'ajak' => ['pexels-8441840.jpg', 'Dua guru berlutut memeluk sekelompok anak di ruang kelas'],
    ];

    // Kartu "kabar anak" di hero yang berganti otomatis: [nama, kelompok, [[ikon, jam, judul, keterangan, warna]]]
    $kabar = [
        ['Kayla', 'Batita', [['🌤️', '07.15', 'Tiba di daycare', 'Sehat dan ceria', 'bg-mint'], ['🧱', '09.30', 'Bermain balok', 'Bersama teman kelompok Batita', 'bg-yellow'], ['😴', '12.30', 'Tidur siang', 'Tidur nyenyak', 'bg-lavender'], ['🏠', '16.20', 'Dijemput Ayah', 'Penjemput terdaftar', 'bg-blue']]],
        ['Raka', 'Balita', [['🌤️', '07.30', 'Tiba di daycare', 'Datang dengan semangat', 'bg-mint'], ['🎨', '10.00', 'Mewarnai', 'Menggambar rumah dan pohon', 'bg-coral'], ['🍚', '12.00', 'Makan siang', 'Makan dengan lahap', 'bg-yellow'], ['🏠', '16.45', 'Dijemput Ibu', 'Penjemput terdaftar', 'bg-blue']]],
        ['Nadia', 'Prasekolah', [['🌤️', '08.00', 'Tiba di daycare', 'Diantar Nenek', 'bg-mint'], ['🎵', '09.45', 'Bernyanyi bersama', 'Menyanyi dengan teman-teman', 'bg-lavender'], ['📖', '11.00', 'Membaca cerita', 'Mendengarkan dongeng', 'bg-yellow'], ['🏠', '17.00', 'Dijemput Paman', 'Izin penjemputan disetujui', 'bg-blue']]],
    ];

    // Komitmen untuk orang tua (carousel kartu): [ikon, judul, isi, warna]
    $komitmen = [
        ['🧑‍🏫', 'Pengasuh terjadwal', 'Setiap kelompok usia punya pengasuh dengan jadwal shift yang jelas.', 'bg-mint'],
        ['🪪', 'Penjemput terdaftar', 'Anak hanya dilepas ke penjemput yang Anda daftarkan atau yang izinnya sudah disetujui.', 'bg-coral'],
        ['🕒', 'Waktu tercatat', 'Jam datang dan jam pulang anak tersimpan setiap hari.', 'bg-blue'],
        ['🧩', 'Sesuai usia', 'Anak berkegiatan bersama teman sebaya di kelompok usianya.', 'bg-lavender'],
        ['📅', 'Rutinitas jelas', 'Sarapan, bermain, belajar, dan tidur siang mengikuti jadwal kegiatan harian.', 'bg-yellow'],
        ['🧸', 'Kondisi anak dicatat', 'Pengasuh mencatat kondisi anak saat ia tiba di daycare.', 'bg-mint'],
    ];
@endphp
<body class="min-h-screen bg-cream font-sans text-ink antialiased">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-ta focus:border-[1.5px] focus:border-ink focus:bg-yellow focus:px-4 focus:py-2 focus:text-sm focus:font-semibold">Lewati ke konten</a>

    {{-- Header --}}
    <header class="sticky top-0 z-40 border-b-[1.5px] border-ink bg-cream/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5" aria-label="TitipAnak, beranda">
                <img src="{{ asset('images/logo-ikon-96.png') }}" alt="" width="96" height="96" class="size-10 shrink-0 rounded-ta">
                <span class="text-xl font-bold tracking-tight">TitipAnak</span>
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi halaman">
                @foreach (['#fitur' => 'Untuk Anda', '#alur' => 'Sehari di sini', '#kelompok' => 'Kelompok usia', '#paket' => 'Paket'] as $href => $label)
                    <a href="{{ $href }}" class="rounded-ta px-3 py-2 text-sm font-medium transition hover:bg-yellow">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ $masuk ? route('dashboard') : route('login') }}" class="{{ $tombol }} bg-yellow">
                    {{ $masuk ? 'Ke Dashboard' : 'Masuk' }}
                </a>
            </div>
        </div>

        {{-- Menu mobile tanpa JavaScript: <details> --}}
        <details class="group border-t-[1.5px] border-ink md:hidden">
            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between px-4 text-sm font-semibold marker:content-none [&::-webkit-details-marker]:hidden">
                <span>Menu</span>
                <span aria-hidden="true" class="transition group-open:rotate-180">▾</span>
            </summary>
            <nav class="bg-paper px-4 pb-3" aria-label="Navigasi halaman (mobile)">
                <ul class="grid gap-1">
                    @foreach (['#fitur' => 'Untuk Anda', '#alur' => 'Sehari di sini', '#kelompok' => 'Kelompok usia', '#paket' => 'Paket'] as $href => $label)
                        <li><a href="{{ $href }}" class="block rounded-ta px-3 py-2.5 text-sm font-medium hover:bg-yellow">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </details>
    </header>

    <main id="konten" class="overflow-x-clip">
        {{-- Hero --}}
        <section class="mx-auto grid min-h-[calc(100svh-7rem)] max-w-6xl items-center gap-10 px-4 py-12 sm:px-6 md:min-h-[calc(100svh-4.1rem)] lg:grid-cols-[1.1fr_0.9fr]" aria-labelledby="judul-hero">
            <div class="grid gap-6">
                <span class="w-fit rounded-full border-[1.5px] border-ink bg-mint px-4 py-1.5 text-xs font-semibold">Layanan penitipan anak (daycare)</span>
                <h1 id="judul-hero" class="text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                    Titip anak dengan <span class="rounded-ta border-[1.5px] border-ink bg-yellow px-2">tenang</span>, pantau dengan mudah.
                </h1>
                <p class="max-w-xl text-lg text-muted">
                    Bekerja tanpa cemas. Pilih paket yang cocok, daftarkan penjemput yang Anda percaya,
                    dan ketahui anak Anda selalu didampingi pengasuh sepanjang hari.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ $masuk ? route('dashboard') : route('login') }}" class="{{ $tombol }} bg-yellow">
                        {{ $masuk ? 'Buka dashboard' : 'Masuk ke aplikasi' }}
                    </a>
                    @if ($paket->isNotEmpty())
                        <a href="#paket" class="{{ $tombol }} bg-paper">Lihat paket layanan</a>
                    @endif
                </div>
            </div>

            {{-- Ilustrasi (dekoratif): tumpukan kartu "kabar anak" yang dilempar bergantian --}}
            <div class="relative mx-auto w-full max-w-md pt-8" aria-hidden="true">
                <div data-kabar class="grid">
                    @foreach ($kabar as [$nama, $kelompokAnak, $momen])
                        <div data-kabar-kartu data-posisi="{{ $loop->index }}" class="kabar-kartu {{ $loop->first ? 'kabar-aktif' : '' }} overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                            <div class="flex items-center justify-between gap-2 border-b-[1.5px] border-ink px-5 py-3 {{ ['bg-lavender', 'bg-mint', 'bg-blue'][$loop->index % 3] }}">
                                <div class="text-lg font-bold">Kabar {{ $nama }} hari ini</div>
                                <span class="rounded-full border border-ink bg-paper px-3 py-1 text-xs font-semibold">Contoh · {{ $kelompokAnak }}</span>
                            </div>
                            <div class="grid gap-3 p-5">
                                @foreach ($momen as [$ikon, $jam, $judul, $ket, $warna])
                                    <div style="--i: {{ $loop->index }}" class="kabar-item flex items-center gap-3 rounded-ta border-[1.5px] border-ink bg-cream px-3 py-2.5">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-full border border-ink text-lg {{ $warna }}">{{ $ikon }}</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold">{{ $judul }}</div>
                                            <div class="truncate text-xs text-muted">{{ $ket }}</div>
                                        </div>
                                        <span class="shrink-0 rounded-full border border-ink bg-paper px-2.5 py-0.5 text-xs font-semibold">{{ $jam }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 flex justify-center gap-1.5">
                    @foreach ($kabar as $k)
                        <span data-kabar-dot class="h-2 w-2 rounded-full border border-ink bg-paper transition-all duration-300"></span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Yang bisa dilakukan orang tua --}}
        <section id="fitur" class="border-y-[1.5px] border-ink bg-paper py-16" aria-labelledby="judul-fitur">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="mb-10 max-w-2xl">
                    <h2 id="judul-fitur" class="text-3xl font-bold tracking-tight sm:text-4xl">Dibuat untuk ketenangan Anda</h2>
                    <p class="mt-2 text-muted">Semua yang orang tua butuhkan untuk menitipkan anak, dalam satu aplikasi.</p>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    @foreach ([
                        ['🧒', 'Daftarkan anak', 'bg-blue', ['Bandingkan paket layanan dan pilih yang paling cocok', 'Daftarkan anak Anda ke kelompok usianya', 'Bayar tagihan dan baca laporan anak'], 'daftar', 'object-center'],
                        ['🪪', 'Penjemputan aman', 'bg-mint', ['Daftarkan penjemput yang Anda percaya', 'Ajukan izin penjemputan kalau ada yang menggantikan', 'Anak tidak dilepas ke orang yang tidak terdaftar'], 'jemput', 'object-[50%_30%]'],
                        ['🧸', 'Hari anak terjadwal', 'bg-yellow', ['Kegiatan harian sesuai kelompok usia: bermain, belajar, tidur siang', 'Anak didampingi pengasuh yang sudah terjadwal', 'Waktu datang dan pulang anak tercatat'], 'jadwal', 'object-center'],
                    ] as [$ikon, $judul, $warna, $butir, $kunciFoto, $posisi])
                        <article class="grid content-start gap-4 overflow-hidden rounded-ta border-[1.5px] border-ink bg-cream">
                            @if ($ada($gambar[$kunciFoto][0]))
                                <img src="{{ asset('images/landing/'.$gambar[$kunciFoto][0]) }}" alt="{{ $gambar[$kunciFoto][1] }}" loading="lazy" decoding="async" class="aspect-[16/10] w-full border-b-[1.5px] border-ink object-cover {{ $posisi }}">
                            @endif
                            <div class="grid content-start gap-4 px-6 pb-6 {{ $ada($gambar[$kunciFoto][0]) ? '' : 'pt-6' }}">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-12 place-items-center rounded-ta border-[1.5px] border-ink text-2xl {{ $warna }}" aria-hidden="true">{{ $ikon }}</span>
                                    <h3 class="text-xl font-bold">{{ $judul }}</h3>
                                </div>
                                <ul class="grid gap-2.5 text-sm">
                                    @foreach ($butir as $b)
                                        <li class="flex gap-3"><span class="mt-1.5 size-2.5 shrink-0 rounded-full border-[1.5px] border-ink {{ $warna }}" aria-hidden="true"></span><span>{{ $b }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Carousel suasana daycare --}}
        @if ($foto->isNotEmpty())
            <section id="suasana" data-carousel role="region" aria-roledescription="carousel" aria-labelledby="judul-suasana" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div class="max-w-2xl">
                        <h2 id="judul-suasana" class="text-3xl font-bold tracking-tight sm:text-4xl">Suasana di daycare</h2>
                        <p class="mt-2 text-muted">Tempat bermain yang hangat, dengan pengasuh yang dekat dengan anak-anak.</p>
                    </div>

                    @if ($foto->count() > 1)
                        <div class="flex items-center gap-2">
                            <button type="button" data-toggle class="{{ $tombolBulat }} w-auto px-4 text-sm font-semibold">Jeda</button>
                            <button type="button" data-prev class="{{ $tombolBulat }}" aria-label="Foto sebelumnya">←</button>
                            <button type="button" data-next class="{{ $tombolBulat }}" aria-label="Foto berikutnya">→</button>
                        </div>
                    @endif
                </div>

                <div data-track tabindex="0" aria-label="Foto suasana daycare" class="relative flex snap-x snap-mandatory overflow-x-auto scroll-smooth rounded-ta border-[1.5px] border-ink bg-paper [scrollbar-width:none] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow [&::-webkit-scrollbar]:hidden">
                    @foreach ($foto as [$berkas, $alt, $judul, $isi, $posisi])
                        <figure role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $loop->count }}" class="m-0 w-full shrink-0 snap-center">
                            <img src="{{ asset('images/landing/'.$berkas) }}" alt="{{ $alt }}" loading="lazy" decoding="async" class="block aspect-[3/2] w-full object-cover sm:aspect-[2/1] lg:aspect-auto lg:h-[22rem] {{ $posisi }}">
                            <figcaption class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t-[1.5px] border-ink bg-paper px-5 py-4">
                                <div class="min-w-0">
                                    <div class="font-bold">{{ $judul }}</div>
                                    <p class="text-sm text-muted">{{ $isi }}</p>
                                </div>
                                <span class="shrink-0 rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold">{{ $loop->iteration }} / {{ $loop->count }}</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    @if ($foto->count() > 1)
                        <div class="flex items-center gap-3" role="group" aria-label="Pilih foto">
                            @foreach ($foto as $f)
                                <button type="button" data-dot aria-label="Ke foto {{ $loop->iteration }}" class="relative size-3.5 rounded-full border-[1.5px] border-ink bg-paper transition after:absolute after:-inset-2 after:content-[''] aria-[current=true]:bg-yellow aria-[current=true]:scale-125"></button>
                            @endforeach
                        </div>
                    @else
                        <span></span>
                    @endif
                    <p class="text-xs text-muted">Foto ilustrasi dari Pexels.</p>
                </div>
            </section>
        @endif

        {{-- Cara kerja (setiap langkah punya foto) --}}
        <section id="alur" class="mx-auto max-w-6xl px-4 py-16 sm:px-6" aria-labelledby="judul-alur">
            <div class="mb-10 max-w-2xl">
                <h2 id="judul-alur" class="text-3xl font-bold tracking-tight sm:text-4xl">Sehari di TitipAnak</h2>
                <p class="mt-2 text-muted">Dari anak diantar sampai dijemput, setiap langkah tercatat.</p>
            </div>

            <ol class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Antar anak', 'Pengasuh menyambut anak dan mencatat kedatangannya beserta kondisinya hari itu.', 'bg-yellow', 'pexels-8441839.jpg', 'Anak-anak dan guru bermain bersama di ruang kelas', 'object-center'],
                    ['Anak beraktivitas', 'Anak mengikuti jadwal kegiatan sesuai usianya: bermain, belajar, dan berkreasi.', 'bg-blue', 'pexels-8422170.jpg', 'Anak-anak membentuk plastisin di meja kelas', 'object-center'],
                    ['Makan dan istirahat', 'Makan siang dan tidur siang berjalan teratur sesuai jadwal kegiatan kelompok usianya.', 'bg-mint', 'pexels-8466773.jpg', 'Anak-anak makan bersama di meja warna-warni', 'object-center'],
                    ['Jemput dengan aman', 'Penjemput dicocokkan dengan daftar yang Anda daftarkan atau izin penjemputan yang sudah disetujui.', 'bg-lavender', 'pexels-6184773.jpg', 'Seorang ibu memeluk putrinya dengan hangat', 'object-[50%_30%]'],
                ] as [$judul, $isi, $warna, $berkas, $alt, $posisi])
                    <li class="grid content-start gap-3 overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                        @if ($ada($berkas))
                            <img src="{{ asset('images/landing/'.$berkas) }}" alt="{{ $alt }}" loading="lazy" decoding="async" class="aspect-[4/3] w-full border-b-[1.5px] border-ink object-cover {{ $posisi }}">
                        @endif
                        <div class="grid content-start gap-2 px-5 pb-5 {{ $ada($berkas) ? '' : 'pt-5' }}">
                            <div class="flex items-center gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full border-[1.5px] border-ink font-bold {{ $warna }}" aria-hidden="true">{{ $loop->iteration }}</span>
                                <h3 class="text-lg font-bold leading-tight">{{ $judul }}</h3>
                            </div>
                            <p class="text-sm text-muted">{{ $isi }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Komitmen untuk orang tua (carousel kartu) --}}
        <section id="komitmen" data-carousel data-multi role="region" aria-roledescription="carousel" aria-labelledby="judul-komitmen" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <h2 id="judul-komitmen" class="text-3xl font-bold tracking-tight sm:text-4xl">Komitmen kami untuk Anda</h2>
                    <p class="mt-2 text-muted">Hal-hal yang kami jaga supaya Anda bisa bekerja dengan tenang.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" data-prev class="{{ $tombolBulat }}" aria-label="Komitmen sebelumnya">←</button>
                    <button type="button" data-next class="{{ $tombolBulat }}" aria-label="Komitmen berikutnya">→</button>
                </div>
            </div>

            <div data-track tabindex="0" aria-label="Daftar komitmen" class="relative flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-2 [scrollbar-width:none] focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow [&::-webkit-scrollbar]:hidden">
                @foreach ($komitmen as [$ikon, $judul, $isi, $warna])
                    <article role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $loop->count }}" class="grid w-[82%] shrink-0 snap-start content-start gap-3 rounded-ta border-[1.5px] border-ink p-6 sm:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.7rem)] {{ $warna }}">
                        <span class="grid size-14 place-items-center rounded-ta border-[1.5px] border-ink bg-paper text-3xl" aria-hidden="true">{{ $ikon }}</span>
                        <h3 class="text-xl font-bold">{{ $judul }}</h3>
                        <p class="text-sm">{{ $isi }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Keamanan penjemputan --}}
        <section class="border-y-[1.5px] border-ink bg-coral/60 py-14" aria-labelledby="judul-aman">
            <div class="mx-auto grid max-w-6xl items-center gap-8 px-4 sm:px-6 md:grid-cols-[auto_1fr]">
                @if ($ada($gambar['aman'][0]))
                    <img src="{{ asset('images/landing/'.$gambar['aman'][0]) }}" alt="{{ $gambar['aman'][1] }}" loading="lazy" decoding="async" class="aspect-[4/5] w-44 -rotate-3 rounded-ta border-[1.5px] border-ink object-cover sm:w-56">
                @else
                    <span class="grid size-20 place-items-center rounded-ta border-[1.5px] border-ink bg-paper text-4xl" aria-hidden="true">🪪</span>
                @endif
                <div>
                    <h2 id="judul-aman" class="text-2xl font-bold tracking-tight sm:text-3xl">Anak hanya pulang bersama orang yang tepat</h2>
                    <p class="mt-2 max-w-3xl">
                        Anda mendaftarkan penjemput lebih dulu. Kalau ada yang menggantikan, ajukan izin penjemputan
                        dan pengasuh akan memeriksanya saat anak dijemput. Penjemput yang tidak cocok ditolak dan anak tidak dilepas.
                    </p>
                </div>
            </div>
        </section>

        {{-- Pita foto yang berjalan otomatis (berhenti saat disorot; dimatikan kalau gerak dikurangi) --}}
        @if ($pita->isNotEmpty())
            <section class="overflow-hidden border-y-[1.5px] border-ink bg-yellow py-10" aria-labelledby="judul-pita">
                <div class="mx-auto mb-6 max-w-6xl px-4 sm:px-6">
                    <h2 id="judul-pita" class="text-2xl font-bold tracking-tight sm:text-3xl">Momen kecil, senyum besar 😊</h2>
                </div>
                <div class="overflow-hidden motion-reduce:overflow-x-auto">
                    <div class="pita-jalan flex w-max gap-4 pr-4 py-2">
                        @foreach ([1, 2] as $salinan)
                            @foreach ($pita as [$berkas, $alt, $posisi])
                                <img src="{{ asset('images/landing/'.$berkas) }}" alt="{{ $salinan === 1 ? $alt : '' }}" @if ($salinan === 2) aria-hidden="true" @endif loading="lazy" decoding="async"
                                     class="h-44 w-60 shrink-0 rounded-ta border-[1.5px] border-ink object-cover sm:h-56 sm:w-72 {{ $posisi }} {{ $loop->odd ? 'rotate-1' : '-rotate-1' }}">
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Kelompok usia --}}
        @if ($kelompok->isNotEmpty())
            <section id="kelompok" class="mx-auto max-w-6xl px-4 py-16 sm:px-6" aria-labelledby="judul-kelompok">
                <div class="mb-10 max-w-2xl">
                    <h2 id="judul-kelompok" class="text-3xl font-bold tracking-tight sm:text-4xl">Dikelompokkan sesuai usia</h2>
                    <p class="mt-2 text-muted">Setiap kelompok punya pengasuh dan jadwal kegiatan sendiri.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($kelompok as $k)
                        <article class="grid content-start gap-2 rounded-ta border-[1.5px] border-ink bg-paper p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-lg font-bold">{{ $k->nama }}</h3>
                                <span class="shrink-0 rounded-full border border-ink px-3 py-1 text-xs font-semibold {{ $palet[$loop->index % count($palet)] }}">{{ $usia($k) }}</span>
                            </div>
                            @if ($k->deskripsi)
                                <p class="text-sm text-muted">{{ $k->deskripsi }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Paket layanan --}}
        @if ($paket->isNotEmpty())
            @php
                // Kalimat "cocok untuk siapa" dicocokkan dari nama paket; paket dengan nama lain tidak mendapat baris ini.
                $cocokUntuk = function (string $nama): ?array {
                    $n = mb_strtolower($nama);

                    return match (true) {
                        str_contains($n, 'weekend') => ['🌤️', 'Cocok untuk orang tua yang bekerja di akhir pekan atau butuh waktu untuk urusan keluarga.'],
                        str_contains($n, 'full') => ['💼', 'Cocok untuk orang tua yang bekerja penuh waktu dan ingin anak terjaga seharian.'],
                        str_contains($n, 'pagi') => ['🌅', 'Cocok untuk orang tua dengan jam kerja pagi atau yang bisa menjemput di siang hari.'],
                        str_contains($n, 'siang') => ['☀️', 'Cocok untuk orang tua dengan jam kerja siang sampai sore.'],
                        default => null,
                    };
                };

                $termasuk = [
                    ['🧑‍🏫', 'Pengasuh terjadwal', 'Setiap kelompok usia dijaga pengasuh dengan jadwal shift yang jelas.', 'bg-mint'],
                    ['🪪', 'Penjemputan aman', 'Anak hanya dilepas ke penjemput terdaftar atau yang izinnya sudah disetujui.', 'bg-coral'],
                    ['🕒', 'Kehadiran tercatat', 'Jam datang dan jam pulang anak tersimpan setiap hari.', 'bg-blue'],
                    ['📅', 'Kegiatan harian', 'Sarapan, bermain, belajar, dan tidur siang sesuai usia anak.', 'bg-lavender'],
                ];
            @endphp

            <section id="paket" class="border-t-[1.5px] border-ink bg-paper py-16" aria-labelledby="judul-paket">
                <div class="mx-auto max-w-6xl px-4 sm:px-6">
                    <div class="mb-10 max-w-2xl">
                        <h2 id="judul-paket" class="text-3xl font-bold tracking-tight sm:text-4xl">Pilih paket yang pas untuk keluarga Anda</h2>
                        <p class="mt-2 text-muted">
                            Setiap keluarga punya jam kerja yang berbeda, jadi kami sediakan beberapa pilihan. Mulai dari penitipan seharian
                            sampai setengah hari, supaya Anda hanya membayar sesuai kebutuhan. Tarif per bulan kecuali disebutkan lain.
                        </p>
                    </div>

                    {{-- Nilai yang didapat di setiap paket --}}
                    <div class="mb-8 rounded-ta border-[1.5px] border-ink bg-cream p-5 sm:p-6">
                        <h3 class="text-lg font-bold">Apa pun paketnya, anak Anda mendapat:</h3>
                        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($termasuk as [$ikon, $judul, $isi, $warna])
                                <li class="flex gap-3">
                                    <span class="grid size-11 shrink-0 place-items-center rounded-ta border-[1.5px] border-ink text-xl {{ $warna }}" aria-hidden="true">{{ $ikon }}</span>
                                    <div>
                                        <div class="font-semibold leading-tight">{{ $judul }}</div>
                                        <p class="mt-0.5 text-sm text-muted">{{ $isi }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($paket as $p)
                            @php($cocok = $cocokUntuk($p->nama))
                            @php($unggulan = $loop->first && $paket->count() > 1)
                            <article class="relative grid content-between gap-5 rounded-ta border-[1.5px] border-ink p-5 {{ $unggulan ? 'bg-yellow' : 'bg-cream' }}">
                                @if ($unggulan)
                                    <span class="absolute -top-3 right-4 rounded-full border-[1.5px] border-ink bg-yellow px-3 py-0.5 text-xs font-bold">Paling lengkap</span>
                                @endif

                                <div class="grid gap-3">
                                    <span class="w-fit rounded-full border border-ink px-3 py-1 text-xs font-semibold {{ $palet[$loop->index % count($palet)] }}">{{ $p->nama }}</span>
                                    <p class="text-sm">{{ $p->deskripsi ?: 'Hubungi kami untuk rincian paket ini.' }}</p>
                                    @if ($cocok)
                                        <p class="flex gap-2 rounded-ta border border-dashed border-ink bg-paper px-3 py-2 text-xs text-muted">
                                            <span aria-hidden="true">{{ $cocok[0] }}</span>
                                            <span>{{ $cocok[1] }}</span>
                                        </p>
                                    @endif
                                </div>

                                <div class="grid gap-3">
                                    <div class="text-2xl font-bold">Rp {{ number_format((float) $p->harga, 0, ',', '.') }}</div>
                                    <a href="{{ $urlDaftar }}" class="{{ $tombol }} w-full {{ $unggulan ? 'bg-ink text-paper' : 'bg-yellow' }}" aria-label="Daftar paket {{ $p->nama }}">
                                        Daftar paket ini
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <p class="mt-6 text-center text-sm text-muted">
                        Masih ragu memilih? Masuk ke aplikasi untuk membandingkan paket dan mendaftarkan anak Anda dengan tenang.
                    </p>
                </div>
            </section>
        @endif

        {{-- Ajakan akhir (untuk orang tua) --}}
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6" aria-labelledby="judul-cta">
            <div class="grid items-center gap-8 overflow-hidden rounded-ta border-[1.5px] border-ink bg-yellow p-8 sm:p-12 {{ $ada($gambar['ajak'][0]) ? 'md:grid-cols-[1.2fr_0.8fr]' : '' }}">
                <div class="grid content-start gap-5">
                    <div>
                        <h2 id="judul-cta" class="text-3xl font-bold tracking-tight sm:text-4xl">Siap menitipkan si kecil dengan tenang?</h2>
                        <p class="mt-2 max-w-xl">Masuk dengan akun Anda untuk memilih paket layanan dan mendaftarkan anak.</p>
                    </div>
                    <a href="{{ $masuk ? route('dashboard') : route('login') }}" class="{{ $tombol }} w-fit bg-ink text-paper">
                        {{ $masuk ? 'Buka dashboard' : 'Masuk sekarang' }}
                    </a>
                </div>
                @if ($ada($gambar['ajak'][0]))
                    <img src="{{ asset('images/landing/'.$gambar['ajak'][0]) }}" alt="{{ $gambar['ajak'][1] }}" loading="lazy" decoding="async" class="aspect-[4/3] w-full rotate-2 rounded-ta border-[1.5px] border-ink object-cover">
                @endif
            </div>
        </section>
    </main>

    <x-footer />

    <script>
        (() => {
            const kurangiGerak = matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Carousel: scroll-snap + tombol. Mode biasa = satu slide penuh (titik + putar otomatis yang bisa dijeda);
            // mode data-multi = beberapa kartu sekaligus (tombol saja). Bisa digeser: sentuhan lewat scroll bawaan, mouse lewat drag.
            document.querySelectorAll('[data-carousel]').forEach((root) => {
                const track = root.querySelector('[data-track]');
                const slides = [...track.children];
                const multi = root.hasAttribute('data-multi');
                const dots = [...root.querySelectorAll('[data-dot]')];
                const toggle = root.querySelector('[data-toggle]');
                const perilaku = kurangiGerak ? 'auto' : 'smooth';
                let index = 0;
                let putar = !kurangiGerak;
                let tahan = false;

                track.querySelectorAll('img').forEach((g) => (g.draggable = false));

                const tampilkan = (i) => {
                    index = i;
                    dots.forEach((d, n) => (n === i ? d.setAttribute('aria-current', 'true') : d.removeAttribute('aria-current')));
                };

                const geser = (arah) => {
                    if (multi) {
                        const langkah = slides.length > 1 ? slides[1].offsetLeft - slides[0].offsetLeft : track.clientWidth;
                        const ujung = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
                        if (arah > 0 && ujung) return track.scrollTo({ left: 0, behavior: perilaku });
                        if (arah < 0 && track.scrollLeft <= 4) return track.scrollTo({ left: track.scrollWidth, behavior: perilaku });
                        return track.scrollBy({ left: arah * langkah, behavior: perilaku });
                    }
                    const n = (index + arah + slides.length) % slides.length;
                    track.scrollTo({ left: slides[n].offsetLeft, behavior: perilaku });
                };

                const atur = (nilai) => {
                    putar = nilai;
                    if (toggle) toggle.textContent = putar ? 'Jeda' : 'Putar';
                };

                if (!multi) {
                    const pengamat = new IntersectionObserver((entri) => {
                        entri.forEach((e) => e.isIntersecting && tampilkan(slides.indexOf(e.target)));
                    }, { root: track, threshold: 0.6 });
                    slides.forEach((s) => pengamat.observe(s));
                    dots.forEach((d, n) => d.addEventListener('click', () => track.scrollTo({ left: slides[n].offsetLeft, behavior: perilaku })));
                }

                root.querySelector('[data-prev]')?.addEventListener('click', () => geser(-1));
                root.querySelector('[data-next]')?.addEventListener('click', () => geser(1));
                toggle?.addEventListener('click', () => atur(!putar));

                // Geser dengan mouse (drag). Layar sentuh sudah bisa di-swipe lewat scroll bawaan browser.
                let seret = null;
                track.addEventListener('pointerdown', (e) => {
                    if (e.pointerType !== 'mouse' || e.button !== 0) return;
                    seret = { x: e.clientX, kiri: track.scrollLeft, mulai: index };
                    track.setPointerCapture(e.pointerId);
                    track.classList.add('sedang-geser');
                });
                track.addEventListener('pointermove', (e) => {
                    if (seret) track.scrollLeft = seret.kiri - (e.clientX - seret.x);
                });
                const lepaskan = (e) => {
                    if (!seret) return;
                    const dx = e.clientX - seret.x;
                    const { mulai } = seret;
                    seret = null;
                    track.classList.remove('sedang-geser');

                    if (multi) {
                        const langkah = slides.length > 1 ? slides[1].offsetLeft - slides[0].offsetLeft : track.clientWidth;
                        track.scrollTo({ left: Math.round(track.scrollLeft / langkah) * langkah, behavior: perilaku });
                    } else {
                        const n = Math.max(0, Math.min(slides.length - 1, mulai + (dx < -50 ? 1 : dx > 50 ? -1 : 0)));
                        track.scrollTo({ left: slides[n].offsetLeft, behavior: perilaku });
                    }
                };
                track.addEventListener('pointerup', lepaskan);
                track.addEventListener('pointercancel', lepaskan);

                ['mouseenter', 'focusin'].forEach((e) => root.addEventListener(e, () => (tahan = true)));
                ['mouseleave', 'focusout'].forEach((e) => root.addEventListener(e, () => (tahan = false)));

                atur(putar);
                if (!multi && slides.length > 1) {
                    setInterval(() => putar && !tahan && !seret && !document.hidden && geser(1), 5000);
                }
            });

            // Tumpukan kartu "kabar anak" di hero (dekoratif): kartu teratas dilempar keluar, kartu di belakangnya maju,
            // lalu kartu yang dilempar masuk lagi ke tumpukan paling belakang. Kartu teratas juga bisa digeser
            // (mouse dan sentuhan): geser cukup jauh ke kiri/kanan untuk melemparnya, kalau kurang jauh ia kembali.
            document.querySelectorAll('[data-kabar]').forEach((deck) => {
                const kartu = [...deck.querySelectorAll('[data-kabar-kartu]')];
                const dots = [...deck.parentElement.querySelectorAll('[data-kabar-dot]')];
                const n = kartu.length;
                let aktif = 0;
                let sibuk = false;

                const susun = () => {
                    kartu.forEach((k, i) => {
                        const d = (i - aktif + n) % n;
                        k.dataset.posisi = d;
                        k.classList.toggle('kabar-aktif', d === 0);
                    });
                    dots.forEach((d, k) => {
                        d.classList.toggle('bg-ink', k === aktif);
                        d.classList.toggle('bg-paper', k !== aktif);
                        d.classList.toggle('w-5', k === aktif);
                        d.classList.toggle('w-2', k !== aktif);
                    });
                };

                // arah: 1 = dilempar ke kanan, -1 = ke kiri
                const lempar = (arah = 1) => {
                    if (sibuk || document.hidden) return;
                    sibuk = true;
                    const atas = kartu[aktif];
                    const kelas = arah > 0 ? 'kabar-lempar' : 'kabar-lempar-kiri';
                    atas.classList.add(kelas);

                    setTimeout(() => {
                        // Kartu yang sudah terlempar dipindah ke belakang tanpa transisi, lalu memudar masuk.
                        atas.style.transition = 'none';
                        atas.style.opacity = '0';
                        atas.classList.remove(kelas);
                        aktif = (aktif + 1) % n;
                        susun();
                        void atas.offsetWidth;
                        atas.style.transition = '';
                        atas.style.opacity = '';
                        setTimeout(() => (sibuk = false), 650);
                    }, 520);
                };

                let seret = null;
                deck.addEventListener('pointerdown', (e) => {
                    if (sibuk || (e.pointerType === 'mouse' && e.button !== 0) || !kartu[aktif].contains(e.target)) return;
                    seret = { x: e.clientX, id: e.pointerId, dx: 0 };
                    deck.setPointerCapture(e.pointerId);
                    kartu[aktif].style.transition = 'none';
                    sibuk = true;
                });
                deck.addEventListener('pointermove', (e) => {
                    if (!seret || e.pointerId !== seret.id) return;
                    seret.dx = e.clientX - seret.x;
                    kartu[aktif].style.transform = `translateX(${seret.dx}px) rotate(${seret.dx * 0.06}deg)`;
                });
                const lepaskan = (e) => {
                    if (!seret || e.pointerId !== seret.id) return;
                    const { dx } = seret;
                    seret = null;
                    const atas = kartu[aktif];
                    atas.style.transition = '';
                    atas.style.transform = '';
                    sibuk = false;
                    if (Math.abs(dx) > 70) lempar(dx > 0 ? 1 : -1);
                };
                deck.addEventListener('pointerup', lepaskan);
                deck.addEventListener('pointercancel', lepaskan);

                susun();
                if (!kurangiGerak && n > 1) setInterval(() => lempar(1), 4200);
            });
        })();
    </script>
</body>
</html>
