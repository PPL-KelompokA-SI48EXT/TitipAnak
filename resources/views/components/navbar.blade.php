@php
    $user = auth()->user();
    $roles = config('titipanak.roles');
    $roleName = $user->getRoleNames()->first();
    $role = $roles[$roleName] ?? ['label' => 'Tanpa peran', 'badge' => 'bg-cream'];
@endphp

<header class="sticky top-0 z-40 h-16 border-b-[1.5px] border-ink bg-cream">
    <div class="flex h-full items-center gap-3 px-4 sm:px-6">
        <button type="button" class="grid size-10 place-items-center rounded-ta border-[1.5px] border-ink bg-paper md:hidden"
            @click="sidebarOpen = !sidebarOpen" aria-label="Buka menu" :aria-expanded="sidebarOpen">☰</button>

        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <span class="grid size-10 place-items-center rounded-ta border-[1.5px] border-ink bg-yellow font-bold">TA</span>
            <span class="hidden text-lg font-bold sm:inline">TitipAnak</span>
        </a>

        <div class="ml-auto flex items-center gap-3">
            {{-- Menu akun: "Sebagai [peran]". Pemilih akun hanya muncul saat dev_switcher aktif. --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" aria-haspopup="true" :aria-expanded="open"
                    class="flex items-center gap-2 rounded-ta border-[1.5px] border-ink bg-paper px-3 py-1.5 text-sm font-semibold transition hover:-translate-y-0.5">
                    <span class="hidden text-muted sm:inline">Sebagai</span>
                    <span class="rounded-full border border-ink px-3 py-1 text-xs font-semibold {{ $role['badge'] }}">{{ $role['label'] }}</span>
                    <span class="hidden max-w-32 truncate sm:inline">{{ $user->nama }}</span>
                    <span aria-hidden="true">▾</span>
                </button>

                <div x-show="open" x-cloak x-transition.opacity
                    class="absolute right-0 mt-2 max-h-[70vh] w-72 overflow-y-auto rounded-ta border-[1.5px] border-ink bg-paper p-3">
                    <div class="mb-2 border-b border-ink pb-2">
                        <div class="font-bold">{{ $user->nama }}</div>
                        <div class="truncate text-xs text-muted">{{ $user->email }}</div>
                    </div>

                    @if (config('titipanak.dev_switcher'))
                        <x-dev.akun-switcher :current="$user" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
