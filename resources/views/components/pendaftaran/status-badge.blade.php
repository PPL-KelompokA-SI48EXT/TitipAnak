{{-- Badge status pendaftaran. Warna mengikuti docs/frontend-guideline.html bagian "Status". --}}
@props(['status'])

@php
    $warna = [
        'draft' => 'bg-ink text-paper',
        'menunggu_verifikasi' => 'bg-yellow',
        'disetujui' => 'bg-mint',
        'ditolak' => 'bg-coral',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex w-fit items-center whitespace-nowrap rounded-full border border-ink px-3 py-1 text-xs font-semibold',
    $warna[$status] ?? 'bg-paper',
]) }}>
    {{ \App\Models\Pendaftaran::STATUS[$status] ?? $status }}
</span>
