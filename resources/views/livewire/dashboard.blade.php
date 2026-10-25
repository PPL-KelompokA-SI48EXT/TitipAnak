@php
    $user = auth()->user();
    $role = config('titipanak.roles')[$user->getRoleNames()->first()] ?? ['label' => 'Tanpa peran', 'badge' => 'bg-cream'];
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Halo, {{ $user->nama }}</h1>
        <p class="text-muted">Anda masuk sebagai <span class="rounded-full border border-ink px-3 py-0.5 text-xs font-semibold text-ink {{ $role['badge'] }}">{{ $role['label'] }}</span></p>
    </div>

    <div class="rounded-ta border-[1.5px] border-ink bg-yellow p-6">
        <div class="text-xl font-bold">Dashboard sedang disiapkan</div>
        <p class="mt-1 text-sm">Ringkasan khusus {{ $role['label'] }} dikerjakan di PBI-20. Sementara itu gunakan menu di sebelah kiri.</p>
    </div>
</div>
