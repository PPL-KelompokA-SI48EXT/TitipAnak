@props(['current' => null])

@php
    $roles = config('titipanak.roles');
    $akun = \App\Models\User::with('roles:id,name')->where('aktif', true)->orderBy('nama')->get(['id', 'nama', 'email'])
        ->groupBy(fn ($u) => $u->roles->first()?->name ?? '-');
@endphp

<div class="grid gap-3 text-sm" data-dev-switcher>
    <p class="rounded-ta bg-coral px-3 py-1.5 text-xs font-semibold">Mode pengembangan: ganti akun tanpa kata sandi</p>

    @foreach ($roles as $name => $role)
        @continue(! isset($akun[$name]))
        <div>
            <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-muted">Sebagai {{ $role['label'] }}</div>
            <div class="grid gap-1">
                @foreach ($akun[$name] as $u)
                    <form method="POST" action="{{ route('dev.masuk', $u) }}">
                        @csrf
                        <button type="submit" @disabled($current?->is($u))
                            class="flex w-full items-center justify-between gap-2 rounded-lg border-[1.5px] px-3 py-1.5 text-left transition
                                {{ $current?->is($u) ? 'border-ink '.$role['badge'].' font-bold' : 'border-transparent hover:border-ink hover:bg-cream' }}">
                            <span class="truncate">{{ $u->nama }}</span>
                            @if ($current?->is($u))<span aria-hidden="true">✓</span>@endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endforeach

    @if ($current)
        <form method="POST" action="{{ route('dev.keluar') }}" class="border-t border-ink pt-3">
            @csrf
            <button type="submit" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3 py-2 text-sm font-semibold transition hover:bg-coral">Keluar</button>
        </form>
    @endif
</div>
