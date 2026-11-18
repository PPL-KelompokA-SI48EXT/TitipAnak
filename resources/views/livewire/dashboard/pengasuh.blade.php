{{-- PBI-20b: dashboard Pengasuh --}}
@php
    $jam = fn ($x) => substr($x->jam_mulai, 0, 5).' – '.substr($x->jam_selesai, 0, 5);
    $statusInfo = [
        'belum' => ['Belum datang', 'bg-yellow'],
        'hadir' => ['Hadir', 'bg-mint'],
        'pulang' => ['Sudah dijemput', 'bg-blue'],
    ];
    $ringkasShift = $data['shift']->isEmpty()
        ? 'Libur'
        : substr($data['shift']->min('jam_mulai'), 0, 5).'–'.substr($data['shift']->max('jam_selesai'), 0, 5);
@endphp

<div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
    <x-dashboard.kartu warna="bg-blue" ikon="🕒" label="Shift hari ini" :nilai="$ringkasShift" :ket="$data['shift']->isEmpty() ? 'tidak ada tugas' : $data['shift']->count().' shift'" :href="route('pengasuh.jadwal-saya')" />
    <x-dashboard.kartu warna="bg-lavender" ikon="🧒" label="Anak saya" :nilai="$data['jumlahAnak']" ket="tanggung jawab hari ini" />
    <x-dashboard.kartu warna="bg-mint" ikon="🏠" label="Hadir sekarang" :nilai="$data['hadir']" ket="sedang di daycare" :href="route('pengasuh.presensi')" />
    <x-dashboard.kartu warna="bg-yellow" ikon="⏳" label="Belum datang" :nilai="$data['belum']" ket="menunggu check-in" :href="route('pengasuh.presensi')" />
    <x-dashboard.kartu warna="bg-cream" ikon="🚪" label="Sudah dijemput" :nilai="$data['pulang']" ket="selesai hari ini" :href="route('pengasuh.presensi')" />
</div>

<div class="grid gap-6 lg:grid-cols-3 lg:items-start">
    {{-- Daftar anak dan status masuk/keluar --}}
    <section class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper lg:col-span-2" aria-labelledby="judul-anak">
        <header class="flex flex-wrap items-center justify-between gap-2 border-b-[1.5px] border-ink bg-cream px-4 py-3">
            <h2 id="judul-anak" class="text-lg font-bold">Anak yang saya jaga hari ini</h2>
            <a href="{{ route('pengasuh.presensi') }}" class="text-sm font-semibold underline">Catat kedatangan dan kepulangan →</a>
        </header>
        @forelse ($data['daftarAnak'] as $d)
            @php $a = $d['anak']; $h = $d['kehadiran']; @endphp
            <div class="flex flex-wrap items-center gap-3 border-t border-ink px-4 py-3 first:border-t-0" wire:key="anak-{{ $a->id }}">
                <span class="grid size-10 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper font-bold" aria-hidden="true">{{ mb_strtoupper(mb_substr($a->nama_lengkap, 0, 1)) }}</span>
                <div class="min-w-0 flex-1">
                    <div class="font-bold">{{ $a->nama_lengkap }}</div>
                    <div class="text-xs text-muted">
                        @if ($a->kelompokUsia)Kelompok {{ $a->kelompokUsia->nama }}@endif
                        @if ($h) · Datang {{ $h->check_in_pada->format('H:i') }}@endif
                        @if ($h?->check_out_pada) · Pulang {{ $h->check_out_pada->format('H:i') }}@endif
                    </div>
                </div>
                <span class="rounded-full border border-ink px-3 py-1 text-xs font-semibold {{ $statusInfo[$d['status']][1] }}">{{ $statusInfo[$d['status']][0] }}</span>
            </div>
        @empty
            <p class="px-4 py-8 text-center text-sm text-muted">Belum ada anak yang menjadi tanggung jawab Anda hari ini.</p>
        @endforelse
    </section>

    <div class="grid content-start gap-6">
        {{-- Shift hari ini --}}
        <section class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="judul-shift">
            <header class="border-b-[1.5px] border-ink bg-blue px-4 py-3">
                <h2 id="judul-shift" class="text-lg font-bold">Shift hari ini</h2>
            </header>
            @forelse ($data['shift'] as $s)
                <div class="flex items-center justify-between gap-2 border-t border-ink px-4 py-3 first:border-t-0" wire:key="shift-{{ $s->id }}">
                    <span class="font-bold">{{ $s->kelompokUsia?->nama ?? '—' }}</span>
                    <span class="whitespace-nowrap text-sm">{{ $jam($s) }}</span>
                </div>
            @empty
                <p class="px-4 py-6 text-center text-sm text-muted">Anda tidak bertugas hari ini.</p>
            @endforelse
        </section>

        {{-- Kegiatan yang sedang berjalan --}}
        @if ($data['perKelompok']->isNotEmpty())
            <section class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="judul-kegiatan">
                <header class="flex items-center justify-between gap-2 border-b-[1.5px] border-ink bg-lavender px-4 py-3">
                    <h2 id="judul-kegiatan" class="text-lg font-bold">Kegiatan sekarang</h2>
                    <a href="{{ route('pengasuh.jadwal-kegiatan') }}" class="text-sm font-semibold underline">Semua →</a>
                </header>
                @foreach ($data['perKelompok'] as $k)
                    <div class="grid gap-0.5 border-t border-ink px-4 py-3 text-sm first:border-t-0" wire:key="keg-{{ $loop->index }}">
                        <span class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $k['nama'] }}</span>
                        @if ($k['berlangsung'])
                            <span><b>{{ $k['berlangsung']->nama_kegiatan }}</b> <span class="text-muted">{{ $jam($k['berlangsung']) }}</span></span>
                        @elseif ($k['berikutnya'])
                            <span class="text-muted">Berikutnya: <b class="text-ink">{{ $k['berikutnya']->nama_kegiatan }}</b> {{ $jam($k['berikutnya']) }}</span>
                        @else
                            <span class="text-muted">{{ $k['adaJadwal'] ? 'Semua kegiatan hari ini sudah selesai.' : 'Belum ada jadwal kegiatan.' }}</span>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Pengumuman (menunggu PBI-07) --}}
        <div class="rounded-ta border-[1.5px] border-dashed border-ink px-4 py-4 text-sm text-muted">
            <b class="text-ink">Pengumuman</b><br>Segera hadir. Pengumuman dari Admin akan tampil di sini.
        </div>
    </div>
</div>
