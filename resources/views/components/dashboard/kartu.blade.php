@props(['href' => null, 'warna' => 'bg-cream', 'ikon', 'label', 'nilai', 'ket' => ''])
{{-- Kartu angka untuk dashboard. Menjadi tautan bila href diisi. --}}
@php
    $kelas = "group flex items-start gap-3 rounded-ta border-[1.5px] border-ink px-4 py-3 {$warna}".($href ? ' transition hover:-translate-y-0.5' : '');
@endphp
<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif class="{{ $kelas }}">
    <span class="grid size-10 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-xl" aria-hidden="true"><x-ikon :e="$ikon" /></span>
    <span class="min-w-0">
        <span class="block text-xs font-semibold uppercase tracking-wide">{{ $label }}</span>
        <span class="block whitespace-nowrap font-bold leading-tight {{ mb_strlen((string) $nilai) > 6 ? 'text-lg' : 'text-3xl' }}">{{ $nilai }}</span>
        @if ($ket !== '')<span class="block text-xs">{{ $ket }}</span>@endif
    </span>
</{{ $href ? 'a' : 'div' }}>
