<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} · TitipAnak</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream font-sans text-ink antialiased flex flex-col justify-between">
    {{-- Header ringkas --}}
    <header class="w-full px-4 pt-4 sm:px-6 sm:pt-6 md:px-8 md:pt-8">
        <div class="mx-auto w-full max-w-5xl flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="grid size-11 place-items-center rounded-ta border-[1.5px] border-ink bg-yellow font-bold text-lg">TA</span>
                <span class="text-xl font-bold tracking-tight">TitipAnak</span>
            </div>
            <span class="text-xs text-muted font-medium">Layanan Penitipan Anak</span>
        </div>
    </header>

    {{-- Konten Utama Form Login --}}
    <main class="my-auto w-full px-4 py-8 sm:px-6 md:px-8">
        {{ $slot }}
    </main>

    {{-- Footer mepet sisi bawah & samping layar --}}
    <x-footer />
</body>
</html>

