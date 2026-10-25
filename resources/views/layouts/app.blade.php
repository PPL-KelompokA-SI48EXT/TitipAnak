<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'TitipAnak' }} · TitipAnak</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Shell aplikasi setelah login: navbar atas, sidebar kiri, konten, footer. --}}
<body class="min-h-screen bg-paper font-sans text-ink antialiased"
    x-data="{
        sidebarOpen: false,
        collapsed: (() => { try { return localStorage.getItem('sidebar-collapsed') === '1' } catch (e) { return false } })(),
    }"
    x-init="$watch('collapsed', v => { try { localStorage.setItem('sidebar-collapsed', v ? '1' : '0') } catch (e) {} })"
    @keydown.escape.window="sidebarOpen = false">
    <x-navbar />

    <div class="flex">
        <x-sidebar />

        <div class="flex min-h-[calc(100vh-4rem)] min-w-0 flex-1 flex-col">
            <main class="flex-1 px-4 py-8 sm:px-8">
                <div class="mx-auto max-w-5xl">
                    {{ $slot }}
                </div>
            </main>

            <x-footer />
        </div>
    </div>
</body>
</html>
