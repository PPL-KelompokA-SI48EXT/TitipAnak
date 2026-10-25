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

<aside aria-label="Menu utama"
    class="fixed inset-y-0 left-0 top-16 z-30 w-64 shrink-0 -translate-x-full overflow-y-auto border-r-[1.5px] border-ink bg-cream p-4 transition-transform md:sticky md:top-16 md:h-[calc(100vh-4rem)] md:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <nav class="grid gap-5">
        @foreach ($groups as $g)
            <div>
                <div class="mb-1.5 px-3 text-xs font-semibold uppercase tracking-wide text-muted">{{ $g['group'] }}</div>
                <ul class="grid gap-1">
                    @foreach ($g['items'] as $item)
                        @php
                            $ada = $item['route'] && Route::has($item['route']);
                            $aktif = $ada && request()->routeIs($item['route']);
                        @endphp
                        <li>
                            @if ($ada)
                                <a href="{{ route($item['route']) }}" @if ($aktif) aria-current="page" @endif
                                    class="flex items-center gap-2.5 rounded-lg border-[1.5px] px-3 py-2 text-sm font-medium transition
                                        {{ $aktif ? 'border-ink bg-yellow font-bold' : 'border-transparent hover:border-ink hover:bg-paper' }}">
                                    <span aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}
                                </a>
                            @else
                                <span aria-disabled="true" class="flex cursor-not-allowed items-center gap-2.5 rounded-lg border-[1.5px] border-transparent px-3 py-2 text-sm text-muted">
                                    <span aria-hidden="true" class="opacity-60">{{ $item['icon'] }}</span>
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    <span class="rounded-full border border-muted px-2 py-0.5 text-[10px] font-semibold">Segera</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>
