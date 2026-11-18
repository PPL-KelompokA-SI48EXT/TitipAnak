@php
    $jam = fn ($x) => substr($x->jam_mulai, 0, 5).' – '.substr($x->jam_selesai, 0, 5);
    $warnaJenis = ['makan' => 'bg-yellow', 'bermain' => 'bg-blue', 'belajar' => 'bg-lavender', 'tidur' => 'bg-mint', 'lainnya' => 'bg-cream'];
@endphp

<div class="grid gap-6" wire:poll.60s>
    <div>
        <h1 class="text-4xl font-bold leading-tight">Jadwal Anak</h1>
        <p class="text-muted">
            Siapa yang menjaga dan apa yang dilakukan anak hari ini · {{ $hariList[$hariIni->dayOfWeekIso] }}, {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}
        </p>
    </div>

    @forelse ($anak as $a)
        @php
            $daftarPengasuh = $pengasuh->get($a->kelompok_usia_id, collect());
            $daftarKegiatan = $kegiatan->get($a->kelompok_usia_id, collect());
        @endphp

        <section wire:key="anak-{{ $a->id }}" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="anak-{{ $a->id }}">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b-[1.5px] border-ink bg-mint px-4 py-3">
                <h2 id="anak-{{ $a->id }}" class="text-lg font-bold">{{ $a->nama_lengkap }}</h2>
                @if ($a->kelompokUsia)
                    <span class="rounded-full border border-ink bg-paper px-2.5 py-0.5 text-xs font-semibold">Kelompok {{ $a->kelompokUsia->nama }}</span>
                @endif
            </header>

            {{-- Pengasuh yang bertugas hari ini --}}
            <div class="border-b-[1.5px] border-ink px-4 py-3">
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-muted">Pengasuh yang menjaga hari ini</h3>
                @if ($daftarPengasuh->isEmpty())
                    <p class="text-sm text-muted">Belum ada pengasuh yang dijadwalkan untuk kelompok ini hari {{ $hariList[$hariIni->dayOfWeekIso] }}.</p>
                @else
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($daftarPengasuh as $s)
                            <li wire:key="shift-{{ $s->id }}" class="flex items-center gap-2 rounded-ta border-[1.5px] border-ink bg-blue px-3 py-1.5 text-sm">
                                <span aria-hidden="true"><x-ikon e="🧑‍🍼" /></span>
                                <span class="font-semibold">{{ $s->pengasuh?->nama ?? 'Pengasuh' }}</span>
                                <span class="text-muted">{{ $jam($s) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Kegiatan harian kelompok --}}
            <div>
                <h3 class="px-4 pt-3 text-sm font-semibold uppercase tracking-wide text-muted">Kegiatan hari ini</h3>
                @if ($daftarKegiatan->isEmpty())
                    <p class="px-4 pb-4 pt-2 text-sm text-muted">Belum ada jadwal kegiatan untuk kelompok ini hari ini.</p>
                @else
                    <ol class="mt-2 grid">
                        @foreach ($daftarKegiatan as $g)
                            @php
                                $berlangsung = $g->jam_mulai <= $sekarang && $sekarang < $g->jam_selesai;
                                $lewat = $g->jam_selesai <= $sekarang;
                            @endphp
                            <li wire:key="kegiatan-{{ $a->id }}-{{ $g->id }}"
                                class="flex flex-wrap items-start gap-x-4 gap-y-1 border-t border-ink px-4 py-3 {{ $berlangsung ? 'bg-yellow/40' : '' }} {{ $lewat ? 'opacity-60' : '' }}"
                                @if ($berlangsung) aria-current="true" @endif>
                                <div class="w-36 shrink-0 whitespace-nowrap font-bold">{{ $jam($g) }}</div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold">{{ $g->nama_kegiatan }}</span>
                                        <span class="rounded-full border border-ink px-2.5 py-0.5 text-xs font-semibold {{ $warnaJenis[$g->jenis_kegiatan] ?? 'bg-cream' }}">{{ $g->labelJenis() }}</span>
                                        @if ($berlangsung)
                                            <span class="rounded-full border border-ink bg-ink px-2.5 py-0.5 text-xs font-semibold text-paper">Sedang berlangsung</span>
                                        @endif
                                    </div>
                                    @if ($g->catatan)
                                        <p class="mt-1 text-sm text-muted">{{ $g->catatan }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </section>
    @empty
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Belum ada anak yang aktif</div>
            <p class="mt-1 text-sm text-muted">Jadwal akan muncul di sini setelah pendaftaran anak Anda disetujui dan anak ditempatkan di sebuah kelompok usia.</p>
        </div>
    @endforelse
</div>
