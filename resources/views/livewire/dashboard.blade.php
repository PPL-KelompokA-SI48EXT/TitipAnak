@php
    $user = auth()->user();
    $role = config('titipanak.roles')[$peran] ?? ['label' => 'Tanpa peran', 'badge' => 'bg-cream'];
    $namaHari = \App\Models\JadwalShift::HARI;
    $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
@endphp

<div class="grid gap-6" wire:poll.60s>
    <div>
        <h1 class="text-4xl font-bold leading-tight">Halo, {{ $user->nama }}</h1>
        <p class="text-muted">
            Anda masuk sebagai <span class="rounded-full border border-ink px-3 py-0.5 text-xs font-semibold text-ink {{ $role['badge'] }}">{{ $role['label'] }}</span>
            · {{ $namaHari[$hariIni->dayOfWeekIso] }}, {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}
        </p>
    </div>

    @switch ($peran)
        @case ('admin')
            @include('livewire.dashboard.admin')
            @break
        @case ('pengasuh')
            @include('livewire.dashboard.pengasuh')
            @break
        @case ('orang_tua')
            @include('livewire.dashboard.orang-tua')
            @break
        @default
            <div class="rounded-ta border-[1.5px] border-ink bg-yellow p-6">
                <div class="text-xl font-bold">Akun Anda belum punya peran</div>
                <p class="mt-1 text-sm">Hubungi Admin agar akun Anda diberi peran.</p>
            </div>
    @endswitch
</div>
