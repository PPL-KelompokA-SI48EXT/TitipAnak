@php
    $user = auth()->user();

    // Hanya tampilkan item yang boleh diakses pengguna ini (permission Spatie).
    $groups = collect(config('menu'))
        ->map(fn ($g) => [
            'group' => $g['group'],
            'items' => collect($g['items'])->filter(fn ($i) => $i['permission'] === null || $user->can($i['permission']))->values(),
        ])
        ->filter(fn ($g) => $g['items']->isNotEmpty());
@endphp

{{-- Latar gelap untuk mobile --}}
<div x-show="sidebarOpen" x-cloak class="fixed inset-0 top-16 z-20 bg-ink/50 md:hidden" @click="sidebarOpen = false" aria-hidden="true"></div>

{{--
    Mobile: drawer (buka lewat tombol di navbar).
    Desktop: bisa dilebarkan/diciutkan (state `collapsed` tersimpan di localStorage, lihat layouts/app.blade.php).
--}}
<aside aria-label="Menu utama"
    class="fixed inset-y-0 left-0 top-16 z-30 flex w-64 shrink-0 -translate-x-full flex-col border-r-[1.5px] border-ink bg-cream transition-all duration-200 md:sticky md:top-16 md:h-[calc(100vh-4rem)] md:translate-x-0"
    :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', collapsed ? 'md:w-[4.5rem]' : 'md:w-64']">

    <nav class="grid flex-1 grid-cols-[minmax(0,1fr)] content-start gap-5 overflow-y-auto overflow-x-hidden p-3">
        @foreach ($groups as $g)
            <div>
                <div class="mb-1.5 px-3 text-xs font-semibold uppercase tracking-wide text-muted" :class="collapsed && 'md:hidden'">{{ $g['group'] }}</div>
                {{-- Pemisah tipis saat sidebar diciutkan, menggantikan judul grup --}}
                @unless ($loop->first)
                    <div class="mx-2 mb-2 hidden border-t border-ink/30" :class="collapsed && 'md:block'"></div>
                @endunless
                <ul class="grid grid-cols-[minmax(0,1fr)] gap-1">
                    @foreach ($g['items'] as $item)
                        @php
                            $children = collect($item['children'] ?? [])
                                ->filter(fn ($c) => ($c['permission'] ?? $item['permission']) === null || $user->can($c['permission'] ?? $item['permission']))
                                ->values();
                            $route = $item['route'] ?? null;
                            $ada = $route && Route::has($route);
                            $aktif = $ada && request()->routeIs($route);
                            $anakAktif = $children->contains(fn ($c) => ($c['route'] ?? null) && Route::has($c['route']) && request()->routeIs($c['route']));
                        @endphp
                        <li>
                            @if ($children->isNotEmpty())
                                {{-- Item dengan submenu: klik untuk buka/tutup. Saat sidebar diciutkan, klik melebarkan sidebar dulu. --}}
                                <div x-data="{ open: {{ $anakAktif ? 'true' : 'false' }} }">
                                    <button type="button" @click="collapsed ? (collapsed = false, open = true) : open = !open"
                                        :aria-expanded="open" title="{{ $item['label'] }}"
                                        class="flex w-full items-center gap-3 rounded-lg border-[1.5px] px-3 py-2 text-left text-sm font-medium transition
                                            {{ $anakAktif ? 'border-ink bg-yellow font-bold' : 'border-transparent hover:border-ink hover:bg-paper' }}">
                                        <span class="w-6 shrink-0 text-center text-lg" aria-hidden="true">{{ $item['icon'] }}</span>
                                        <span class="flex-1 truncate" :class="collapsed && 'md:hidden'">{{ $item['label'] }}</span>
                                        <span class="shrink-0 text-xs transition-transform" :class="[open && 'rotate-180', collapsed && 'md:hidden']" aria-hidden="true">▾</span>
                                    </button>

                                    <ul x-show="open" x-cloak :class="collapsed && 'md:hidden'"
                                        class="ml-5 mt-1 grid grid-cols-[minmax(0,1fr)] gap-1 border-l-[1.5px] border-ink/30 pl-2">
                                        @foreach ($children as $child)
                                            @php
                                                $childAda = ($child['route'] ?? null) && Route::has($child['route']);
                                                $childAktif = $childAda && request()->routeIs($child['route']);
                                            @endphp
                                            <li>
                                                @if ($childAda)
                                                    <a href="{{ route($child['route']) }}" @if ($childAktif) aria-current="page" @endif
                                                        class="flex items-center rounded-lg border-[1.5px] px-2 py-1.5 text-sm transition
                                                            {{ $childAktif ? 'border-ink bg-yellow font-bold' : 'border-transparent font-medium hover:border-ink hover:bg-paper' }}">
                                                        <span class="truncate">{{ $child['label'] }}</span>
                                                    </a>
                                                @else
                                                    <span aria-disabled="true" class="flex cursor-not-allowed items-center gap-2 rounded-lg border-[1.5px] border-transparent px-2 py-1.5 text-sm text-muted">
                                                        <span class="flex-1 truncate">{{ $child['label'] }}</span>
                                                        <span class="rounded-full border border-muted px-2 py-0.5 text-[10px] font-semibold">Segera</span>
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif ($ada)
                                <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}" @if ($aktif) aria-current="page" @endif
                                    class="flex items-center gap-3 rounded-lg border-[1.5px] px-3 py-2 text-sm font-medium transition
                                        {{ $aktif ? 'border-ink bg-yellow font-bold' : 'border-transparent hover:border-ink hover:bg-paper' }}">
                                    <span class="w-6 shrink-0 text-center text-lg" aria-hidden="true">{{ $item['icon'] }}</span>
                                    <span class="truncate" :class="collapsed && 'md:hidden'">{{ $item['label'] }}</span>
                                </a>
                            @else
                                <span aria-disabled="true" title="{{ $item['label'] }} (segera)"
                                    class="flex cursor-not-allowed items-center gap-3 rounded-lg border-[1.5px] border-transparent px-3 py-2 text-sm text-muted">
                                    <span class="w-6 shrink-0 text-center text-lg opacity-60" aria-hidden="true">{{ $item['icon'] }}</span>
                                    <span class="flex-1 truncate" :class="collapsed && 'md:hidden'">{{ $item['label'] }}</span>
                                    <span class="rounded-full border border-muted px-2 py-0.5 text-[10px] font-semibold" :class="collapsed && 'md:hidden'">Segera</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Tombol expand / collapse (desktop) --}}
    <div class="hidden border-t-[1.5px] border-ink p-3 md:block">
        <button type="button" @click="collapsed = !collapsed"
            class="flex w-full items-center gap-3 rounded-lg border-[1.5px] border-transparent px-3 py-2 text-sm font-medium transition hover:border-ink hover:bg-paper"
            :aria-label="collapsed ? 'Lebarkan menu' : 'Ciutkan menu'" :title="collapsed ? 'Lebarkan menu' : 'Ciutkan menu'">
            <span class="w-6 shrink-0 text-center text-lg font-bold" x-text="collapsed ? '»' : '«'" aria-hidden="true">«</span>
            <span class="truncate" :class="collapsed && 'md:hidden'">Ciutkan menu</span>
        </button>
    </div>
</aside>
