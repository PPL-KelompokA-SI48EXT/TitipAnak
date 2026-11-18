{{-- PBI-20c: dashboard Orang Tua --}}
@php
    $statusInfo = [
        'belum' => ['Belum datang', 'bg-yellow'],
        'hadir' => ['Sedang di daycare', 'bg-mint'],
        'pulang' => ['Sudah dijemput', 'bg-blue'],
    ];
    $labelStatus = \App\Models\Pendaftaran::STATUS;
    $ringkasPendaftaran = collect($data['pendaftaranPerStatus'])->map(fn ($n, $s) => $n.' '.strtolower($labelStatus[$s] ?? $s))->implode(' · ');
    $perhatian = [];
    if ($data['tagihanTerlambat'] > 0) {
        $perhatian[] = ['⚠️', $data['tagihanTerlambat'].' tagihan sudah lewat jatuh tempo', 'bg-coral', route('orang-tua.tagihan.index')];
    }
    if (($data['pendaftaranPerStatus']['draft'] ?? 0) > 0) {
        $perhatian[] = ['📝', ($data['pendaftaranPerStatus']['draft']).' pendaftaran masih Draft. Lengkapi lalu ajukan.', 'bg-yellow', route('orang-tua.pendaftaran.index')];
    }
    if ($data['tagihanBelumLunas'] > 0 && $data['tagihanTerlambat'] === 0 && $data['jatuhTempoTerdekat']) {
        $perhatian[] = ['💳', 'Tagihan terdekat jatuh tempo '.$data['jatuhTempoTerdekat']->format('d/m/Y'), 'bg-cream', route('orang-tua.tagihan.index')];
    }
@endphp

<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-dashboard.kartu warna="bg-blue" ikon="🧒" label="Anak saya" :nilai="$data['anak']->count()" ket="aktif di daycare" :href="route('orang-tua.kabar-hari-ini')" />
    <x-dashboard.kartu warna="bg-mint" ikon="🏠" label="Sedang di daycare" :nilai="$data['anak']->where('status', 'hadir')->count()" ket="anak yang sudah tiba" :href="route('orang-tua.kabar-hari-ini')" />
    <x-dashboard.kartu warna="bg-yellow" ikon="📝" label="Pendaftaran" :nilai="$data['jumlahPendaftaran']" :ket="$ringkasPendaftaran ?: 'belum ada pendaftaran'" :href="route('orang-tua.pendaftaran.index')" />
    <x-dashboard.kartu warna="bg-coral" ikon="💳" label="Tagihan belum lunas" :nilai="$data['tagihanBelumLunas']" :ket="$rupiah($data['nominalBelumLunas'])" :href="route('orang-tua.tagihan.index')" />
</div>

<div class="grid gap-6 lg:grid-cols-3 lg:items-start">
    {{-- Kabar anak hari ini --}}
    <section class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper lg:col-span-2" aria-labelledby="judul-kabar">
        <header class="flex flex-wrap items-center justify-between gap-2 border-b-[1.5px] border-ink bg-mint px-4 py-3">
            <h2 id="judul-kabar" class="text-lg font-bold">Kabar anak hari ini</h2>
            <a href="{{ route('orang-tua.kabar-hari-ini') }}" class="text-sm font-semibold underline">Lihat selengkapnya →</a>
        </header>
        @forelse ($data['anak'] as $d)
            @php $a = $d['anak']; $h = $d['kehadiran']; @endphp
            <div class="flex flex-wrap items-center gap-3 border-t border-ink px-4 py-3 first:border-t-0" wire:key="anak-{{ $a->id }}">
                <span class="grid size-11 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-lg font-bold" aria-hidden="true">{{ mb_strtoupper(mb_substr($a->nama_lengkap, 0, 1)) }}</span>
                <div class="min-w-0 flex-1">
                    <div class="font-bold">{{ $a->nama_lengkap }}</div>
                    <div class="text-sm text-muted">
                        @if (! $h)
                            Belum tiba di daycare
                        @else
                            Tiba {{ $h->check_in_pada->format('H:i') }}@if ($h->kondisi_check_in) · {{ $h->kondisi_check_in }}@endif
                            @if ($h->check_out_pada) · Dijemput {{ $h->check_out_pada->format('H:i') }}@endif
                        @endif
                    </div>
                </div>
                <span class="rounded-full border border-ink px-3 py-1 text-xs font-semibold {{ $statusInfo[$d['status']][1] }}">{{ $statusInfo[$d['status']][0] }}</span>
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-muted">Belum ada anak yang aktif. Kabar anak muncul setelah pendaftaran disetujui.</p>
        @endforelse
    </section>

    {{-- Perlu diperhatikan --}}
    <section class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="judul-perhatian">
        <header class="border-b-[1.5px] border-ink bg-yellow px-4 py-3">
            <h2 id="judul-perhatian" class="text-lg font-bold">Perlu diperhatikan</h2>
        </header>
        @forelse ($perhatian as [$ikon, $teks, $warna, $tautan])
            <a href="{{ $tautan }}" class="flex items-center gap-3 border-t border-ink px-4 py-3 text-sm transition first:border-t-0 hover:bg-cream">
                <span class="grid size-9 shrink-0 place-items-center rounded-full border-[1.5px] border-ink {{ $warna }}" aria-hidden="true"><x-ikon :e="$ikon" /></span>
                <span>{{ $teks }}</span>
            </a>
        @empty
            <p class="flex items-center gap-2 px-4 py-6 text-sm"><x-ikon e="✅" /> Tidak ada yang perlu Anda urus saat ini.</p>
        @endforelse
    </section>
</div>

{{-- Fitur yang menyusul --}}
<div class="grid gap-3 sm:grid-cols-3">
    @foreach ([['Laporan terbaru', 'Laporan harian dari pengasuh'], ['Foto terbaru', 'Foto kegiatan anak'], ['Pengumuman', 'Kabar dari daycare']] as [$judul, $ket])
        <div class="rounded-ta border-[1.5px] border-dashed border-ink px-4 py-4 text-sm text-muted">
            <b class="text-ink">{{ $judul }}</b><br>Segera hadir. {{ $ket }} akan tampil di sini.
        </div>
    @endforeach
</div>
