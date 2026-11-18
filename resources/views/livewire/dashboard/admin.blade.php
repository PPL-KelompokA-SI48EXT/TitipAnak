{{-- PBI-20a: dashboard Admin --}}
@php
    $perluTindakan = [
        ['⏳', $data['pendaftaranMenunggu'], 'pendaftaran menunggu verifikasi', route('admin.pendaftaran.index')],
        ['💳', $data['pembayaranMenunggu'], 'bukti pembayaran menunggu verifikasi', route('admin.tagihan.index')],
        ['🏠', $data['hadir'], 'anak masih di daycare (belum dijemput)', route('admin.kehadiran.rekap')],
    ];
@endphp

<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-dashboard.kartu warna="bg-blue" ikon="🧒" label="Anak aktif" :nilai="$data['anakAktif']" ket="terdaftar di daycare" />
    <x-dashboard.kartu warna="bg-mint" ikon="🏠" label="Hadir hari ini" :nilai="$data['hadir'] + $data['pulang']" :ket="'dari '.$data['anakAktif'].' anak aktif'" :href="route('admin.kehadiran.rekap')" />
    <x-dashboard.kartu warna="bg-yellow" ikon="⏳" label="Menunggu verifikasi" :nilai="$data['pendaftaranMenunggu']" ket="pendaftaran baru" :href="route('admin.pendaftaran.index')" />
    <x-dashboard.kartu warna="bg-coral" ikon="💳" label="Tagihan belum lunas" :nilai="$data['tagihanBelumLunas']" :ket="$rupiah($data['nominalBelumLunas'])" :href="route('admin.tagihan.index')" />
</div>

<div class="grid gap-6 lg:grid-cols-3 lg:items-start">
    {{-- Kondisi hari ini per kelompok usia --}}
    <section class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper lg:col-span-2" aria-labelledby="judul-kelompok">
        <header class="flex flex-wrap items-center justify-between gap-2 border-b-[1.5px] border-ink bg-cream px-4 py-3">
            <h2 id="judul-kelompok" class="text-lg font-bold">Hari ini per kelompok usia</h2>
            <span class="flex flex-wrap gap-2 text-xs font-semibold">
                <span class="inline-flex items-center gap-1"><span class="size-3 rounded-full border border-ink bg-mint"></span>Hadir</span>
                <span class="inline-flex items-center gap-1"><span class="size-3 rounded-full border border-ink bg-blue"></span>Sudah dijemput</span>
                <span class="inline-flex items-center gap-1"><span class="size-3 rounded-full border border-ink bg-paper"></span>Belum datang</span>
            </span>
        </header>
        @forelse ($data['perKelompok'] as $k)
            <div class="grid gap-2 border-t border-ink px-4 py-3 first:border-t-0" wire:key="kel-{{ $loop->index }}">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <span class="font-bold">{{ $k['nama'] }}</span>
                    <span class="text-sm text-muted">{{ $k['hadir'] }} hadir · {{ $k['pulang'] }} dijemput · {{ $k['belum'] }} belum datang · {{ $k['total'] }} anak</span>
                </div>
                <div class="flex h-4 overflow-hidden rounded-full border-[1.5px] border-ink bg-paper" role="img"
                    aria-label="{{ $k['nama'] }}: {{ $k['hadir'] }} hadir, {{ $k['pulang'] }} dijemput, {{ $k['belum'] }} belum datang">
                    @if ($k['hadir'])<span class="bg-mint" style="width: {{ $k['hadir'] / $k['total'] * 100 }}%"></span>@endif
                    @if ($k['pulang'])<span class="border-l border-ink bg-blue" style="width: {{ $k['pulang'] / $k['total'] * 100 }}%"></span>@endif
                </div>
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-muted">Belum ada anak aktif di kelompok usia mana pun.</p>
        @endforelse
    </section>

    {{-- Perlu tindakan --}}
    <section class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="judul-tindakan">
        <header class="border-b-[1.5px] border-ink bg-yellow px-4 py-3">
            <h2 id="judul-tindakan" class="text-lg font-bold">Perlu tindakan</h2>
        </header>
        @php $adaTindakan = collect($perluTindakan)->contains(fn ($t) => $t[1] > 0); @endphp
        @if ($adaTindakan)
            <ul class="grid">
                @foreach ($perluTindakan as [$ikon, $jumlah, $teks, $tautan])
                    @if ($jumlah > 0)
                        <li class="border-t border-ink first:border-t-0">
                            <a href="{{ $tautan }}" class="flex items-center gap-3 px-4 py-3 transition hover:bg-cream">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-cream" aria-hidden="true"><x-ikon :e="$ikon" /></span>
                                <span class="text-sm"><b class="text-base">{{ $jumlah }}</b> {{ $teks }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @else
            <p class="flex items-center gap-2 px-4 py-6 text-sm"><x-ikon e="✅" /> Semua beres. Tidak ada yang menunggu tindakan.</p>
        @endif
    </section>
</div>

{{-- Kehadiran 7 hari terakhir --}}
<section class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="judul-tujuh">
    <header class="flex flex-wrap items-center justify-between gap-2 border-b-[1.5px] border-ink bg-cream px-4 py-3">
        <h2 id="judul-tujuh" class="text-lg font-bold">Kehadiran 7 hari terakhir</h2>
        <a href="{{ route('admin.kehadiran.rekap') }}" class="text-sm font-semibold underline">Buka rekap kehadiran →</a>
    </header>
    <div class="grid grid-cols-7 items-end gap-2 px-4 pb-3 pt-4" role="img"
        aria-label="Jumlah anak hadir per hari: {{ $data['tujuhHari']->map(fn ($h) => $namaHari[$h['tanggal']->dayOfWeekIso].' '.$h['jumlah'])->implode(', ') }}">
        @foreach ($data['tujuhHari'] as $h)
            @php $adalahHariIni = $h['tanggal']->isSameDay($hariIni); @endphp
            <div class="grid justify-items-center gap-1" wire:key="hari-{{ $loop->index }}">
                <span class="text-sm font-bold">{{ $h['jumlah'] }}</span>
                <div class="flex h-24 w-full max-w-14 items-end">
                    <span class="w-full rounded-t-md border-[1.5px] border-ink {{ $adalahHariIni ? 'bg-yellow' : 'bg-blue' }}"
                        style="height: {{ max(4, $h['jumlah'] / $data['maksHari'] * 100) }}%"></span>
                </div>
                <span class="text-xs {{ $adalahHariIni ? 'font-bold' : 'text-muted' }}">{{ mb_substr($namaHari[$h['tanggal']->dayOfWeekIso], 0, 3) }}</span>
            </div>
        @endforeach
    </div>
</section>
