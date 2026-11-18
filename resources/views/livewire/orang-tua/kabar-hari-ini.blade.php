@php
    $jam = fn ($x) => substr($x->jam_mulai, 0, 5).' – '.substr($x->jam_selesai, 0, 5);
    $warnaJenis = ['makan' => 'bg-yellow', 'bermain' => 'bg-blue', 'belajar' => 'bg-lavender', 'tidur' => 'bg-mint', 'lainnya' => 'bg-cream'];
    $ikonJenis = ['makan' => '🍽️', 'bermain' => '🧩', 'belajar' => '📚', 'tidur' => '😴', 'lainnya' => '⭐'];
    $statusInfo = [
        'belum' => ['Belum datang', 'bg-yellow'],
        'hadir' => ['Sedang di daycare', 'bg-mint'],
        'pulang' => ['Sudah dijemput', 'bg-blue'],
    ];
@endphp

<div class="grid gap-6" wire:poll.60s>
    <div>
        <h1 class="text-4xl font-bold leading-tight">Kabar Hari Ini</h1>
        <p class="text-muted">
            Kabar terbaru anak Anda · {{ $hariList[$hariIni->dayOfWeekIso] }}, {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}
        </p>
    </div>

    @forelse ($kabar as $k)
        @php
            $a = $k['anak'];
            $h = $k['kehadiran'];
            $status = $k['status'];
        @endphp

        <section wire:key="kabar-{{ $a->id }}" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="kabar-judul-{{ $a->id }}">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b-[1.5px] border-ink px-4 py-3 {{ $statusInfo[$status][1] }}">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-lg font-bold" aria-hidden="true">{{ mb_strtoupper(mb_substr($a->nama_lengkap, 0, 1)) }}</span>
                    <div>
                        <h2 id="kabar-judul-{{ $a->id }}" class="text-lg font-bold leading-tight">Kabar {{ $a->nama_lengkap }} hari ini</h2>
                        @if ($a->kelompokUsia)
                            <div class="text-xs">Kelompok {{ $a->kelompokUsia->nama }}</div>
                        @endif
                    </div>
                </div>
                <span class="rounded-full border border-ink bg-paper px-3 py-1 text-xs font-semibold">{{ $statusInfo[$status][0] }}</span>
            </header>

            <ol class="grid gap-3 p-4">
                {{-- 1. Tiba di daycare --}}
                @if ($h)
                    <li class="flex items-center gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-3 py-2.5">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-lg" aria-hidden="true"><x-ikon e="🏠" /></span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold">Tiba di daycare</div>
                            <div class="flex flex-wrap items-center gap-1.5 text-sm text-muted">
                                @if ($h->kondisi_check_in)
                                    <x-ikon :e="$ikonKondisi[$h->kondisi_check_in] ?? '🙂'" /> {{ $h->kondisi_check_in }}
                                @else
                                    Kondisi belum dicatat
                                @endif
                            </div>
                        </div>
                        <span class="rounded-full border border-ink bg-paper px-3 py-1 text-sm font-semibold">{{ $h->check_in_pada->format('H:i') }}</span>
                    </li>
                @else
                    <li class="flex items-center gap-3 rounded-ta border-[1.5px] border-dashed border-ink px-3 py-2.5">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-cream text-lg" aria-hidden="true"><x-ikon e="⏳" /></span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold">Belum tiba</div>
                            <div class="text-sm text-muted">Kabar akan muncul di sini begitu pengasuh mencatat kedatangan {{ $a->nama_lengkap }}.</div>
                        </div>
                    </li>
                @endif

                {{-- 2. Kegiatan (hanya selama anak ada di daycare) --}}
                @if ($status === 'hadir')
                    @if ($k['berlangsung'])
                        @php($g = $k['berlangsung'])
                        <li class="flex items-center gap-3 rounded-ta border-[1.5px] border-ink px-3 py-2.5 {{ $warnaJenis[$g->jenis_kegiatan] ?? 'bg-cream' }}" aria-current="true">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-lg" aria-hidden="true"><x-ikon :e="$ikonJenis[$g->jenis_kegiatan] ?? '⭐'" /></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold">{{ $g->nama_kegiatan }}</span>
                                    <span class="rounded-full border border-ink bg-ink px-2.5 py-0.5 text-xs font-semibold text-paper">Sedang berlangsung</span>
                                </div>
                                @if ($g->catatan)<div class="text-sm">{{ $g->catatan }}</div>@endif
                            </div>
                            <span class="whitespace-nowrap rounded-full border border-ink bg-paper px-3 py-1 text-sm font-semibold">{{ $jam($g) }}</span>
                        </li>
                    @endif

                    @if ($k['berikutnya'])
                        @php($g = $k['berikutnya'])
                        <li class="flex items-center gap-3 rounded-ta border-[1.5px] border-ink bg-paper px-3 py-2.5">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-cream text-lg" aria-hidden="true"><x-ikon :e="$ikonJenis[$g->jenis_kegiatan] ?? '⭐'" /></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold">{{ $g->nama_kegiatan }}</span>
                                    <span class="rounded-full border border-ink px-2.5 py-0.5 text-xs font-semibold">Berikutnya</span>
                                </div>
                                @if ($g->catatan)<div class="text-sm text-muted">{{ $g->catatan }}</div>@endif
                            </div>
                            <span class="whitespace-nowrap rounded-full border border-ink bg-paper px-3 py-1 text-sm font-semibold">{{ $jam($g) }}</span>
                        </li>
                    @endif

                    @if (! $k['berlangsung'] && ! $k['berikutnya'])
                        <li class="rounded-ta border border-dashed border-ink px-3 py-2.5 text-sm text-muted">
                            {{ $k['adaJadwal'] ? 'Semua kegiatan hari ini sudah selesai.' : 'Belum ada jadwal kegiatan untuk kelompok ini hari ini.' }}
                        </li>
                    @endif
                @endif

                {{-- 3. Dijemput --}}
                @if ($status === 'pulang')
                    <li class="flex items-center gap-3 rounded-ta border-[1.5px] border-ink bg-blue px-3 py-2.5">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-lg" aria-hidden="true"><x-ikon e="🚪" /></span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold">Dijemput</div>
                            <div class="text-sm">{{ $h->nama_penjemput ? 'Oleh '.$h->nama_penjemput : 'Penjemput tercatat' }}</div>
                        </div>
                        <span class="rounded-full border border-ink bg-paper px-3 py-1 text-sm font-semibold">{{ $h->check_out_pada->format('H:i') }}</span>
                    </li>
                @elseif ($status === 'hadir')
                    <li class="flex items-center gap-3 rounded-ta border-[1.5px] border-dashed border-ink px-3 py-2.5">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-cream text-lg" aria-hidden="true"><x-ikon e="🚪" /></span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold">Belum dijemput</div>
                            <div class="text-sm text-muted">Jam pulang dan nama penjemput akan tercatat saat anak diserahkan.</div>
                        </div>
                    </li>
                @endif
            </ol>

            <footer class="border-t border-ink bg-cream px-4 py-2.5 text-sm">
                <a href="{{ route('orang-tua.jadwal-anak') }}" class="font-semibold underline">Lihat jadwal lengkap dan pengasuh →</a>
            </footer>
        </section>
    @empty
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Belum ada anak yang aktif</div>
            <p class="mt-1 text-sm text-muted">Kabar anak akan muncul di sini setelah pendaftaran anak Anda disetujui.</p>
        </div>
    @endforelse
</div>
