<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Akun · TitipAnak</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-cream px-4 py-10 font-sans text-ink antialiased">
    {{-- Sementara halaman login (PBI-01) belum ada. Hanya muncul saat dev_switcher aktif. --}}
    <div class="w-full max-w-md rounded-ta border-[1.5px] border-ink bg-paper p-6">
        <div class="mb-4 flex items-center gap-3">
            <span class="grid size-11 place-items-center rounded-ta border-[1.5px] border-ink bg-yellow font-bold">TA</span>
            <div>
                <h1 class="text-xl font-bold leading-tight">Pilih akun</h1>
                <p class="text-sm text-muted">Masuk sebagai peran apa saja untuk mencoba aplikasi.</p>
            </div>
        </div>

        <x-dev.akun-switcher />
    </div>
</body>
</html>
