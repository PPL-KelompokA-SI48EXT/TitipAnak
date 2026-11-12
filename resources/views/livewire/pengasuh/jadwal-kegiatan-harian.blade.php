@php
    $jam = fn ($k) => substr($k->jam_mulai, 0, 5).' – '.substr($k->jam_selesai, 0, 5);
    $warnaJenis = ['makan' => 'bg-yellow', 'bermain' => 'bg-blue', 'belajar' => 'bg-lavender', 'tidur' => 'bg-mint', 'lainnya' => 'bg-cream'];
@endphp

<div class="grid gap-6" wire:poll.60s>
    <div>
        <h1 class="text-4xl font-bold leading-tight">Jadwal Kegiatan Harian</h1>
        <p class="text-muted">
            Panduan rutinitas anak hari ini · {{ $hariList[$hariIni->dayOfWeekIso] }}, {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}
        </p>
    </div>

    @forelse ($kegiatan as $kelompokId => $daftar)
        <section wire:key="kelompok-{{ $kelompokId }}" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="kelompok-{{ $kelompokId }}">
            <header class="border-b-[1.5px] border-ink bg-yellow px-4 py-3">
                <h2 id="kelompok-{{ $kelompokId }}" class="text-lg font-bold">{{ $daftar->first()->kelompokUsia?->nama ?? '—' }}</h2>
            </header>

            <ol class="grid">
                @foreach ($daftar as $g)
                    @php
                        $berlangsung = $g->jam_mulai <= $sekarang && $sekarang < $g->jam_selesai;
                        $lewat = $g->jam_selesai <= $sekarang;
                    @endphp
                    <li wire:key="kegiatan-{{ $g->id }}"
                        class="flex flex-wrap items-start gap-x-4 gap-y-1 border-b border-ink px-4 py-3 last:border-b-0 {{ $berlangsung ? 'bg-yellow/40' : '' }} {{ $lewat ? 'opacity-60' : '' }}"
                        @if ($berlangsung) aria-current="true" @endif>
                        <div class="w-28 shrink-0 font-bold">{{ $jam($g) }}</div>
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
        </section>
    @empty
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada kegiatan hari ini</div>
            <p class="mt-1 text-sm text-muted">Anda tidak punya shift pada hari {{ $hariList[$hariIni->dayOfWeekIso] }}, atau kelompok usia Anda belum punya jadwal kegiatan.</p>
        </div>
    @endforelse
</div>
