@php
    $jam = fn ($j) => substr($j->jam_mulai, 0, 5).' – '.substr($j->jam_selesai, 0, 5);
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
    $sekarang = now()->format('H:i');
    $ikonHari = [1 => '🌱', 2 => '🌼', 3 => '🌈', 4 => '🍀', 5 => '🎉', 6 => '🧸', 7 => '☀️'];
@endphp

<div class="grid gap-8">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Jadwal Saya</h1>
        <p class="text-muted">Shift dan anak yang menjadi tanggung jawab Anda.</p>
    </div>

    {{-- Hari ini --}}
    <section class="grid gap-4" aria-labelledby="judul-hari-ini">
        <h2 id="judul-hari-ini" class="text-2xl font-bold">
            Hari ini <span class="font-normal text-muted">· {{ $hariList[$hariIni->dayOfWeekIso] }}, {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}</span>
        </h2>

        @forelse ($shiftHariIni as $s)
            @php $anak = $s->kelompokUsia?->anak ?? collect(); @endphp
            @php $berlangsung = $sekarang >= substr($s->jam_mulai, 0, 5) && $sekarang < substr($s->jam_selesai, 0, 5); @endphp
            <article wire:key="hari-ini-{{ $s->id }}" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b-[1.5px] border-ink bg-mint px-5 py-4">
                    <div>
                        <div class="text-2xl font-bold leading-tight">{{ $s->kelompokUsia?->nama ?? '—' }}</div>
                        <div class="text-sm">Shift {{ $jam($s) }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($berlangsung)
                            <span class="rounded-full border border-ink bg-yellow px-3 py-1 text-sm font-semibold">● Sedang bertugas</span>
                        @endif
                        <span class="rounded-full border border-ink bg-paper px-3 py-1 text-sm font-semibold">{{ $anak->count() }} anak</span>
                    </div>
                </div>

                <div class="grid gap-4 p-5">
                @if ($anak->isEmpty())
                    <p class="rounded-ta border-[1.5px] border-dashed border-ink bg-cream px-4 py-6 text-center text-sm text-muted">
                        Belum ada anak aktif di kelompok usia ini.
                    </p>
                @else
                    <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($anak as $a)
                            <li wire:key="anak-{{ $s->id }}-{{ $a->id }}" class="flex items-center gap-3 rounded-ta border-[1.5px] border-ink bg-cream px-3 py-2">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full border border-ink font-bold {{ $palet[$loop->index % count($palet)] }}" aria-hidden="true">{{ mb_strtoupper(mb_substr($a->nama_lengkap, 0, 1)) }}</span>
                                <span class="min-w-0 break-words font-semibold">{{ $a->nama_lengkap }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                </div>
            </article>
        @empty
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-10 text-center">
                <div class="text-xl font-bold">Tidak ada shift hari ini</div>
                <p class="mt-1 text-sm text-muted">Anda tidak bertugas pada hari {{ $hariList[$hariIni->dayOfWeekIso] }}.</p>
            </div>
        @endforelse
    </section>

    {{-- Pola mingguan --}}
    <section class="grid gap-4" aria-labelledby="judul-pekan">
        <h2 id="judul-pekan" class="text-2xl font-bold">Jadwal mingguan</h2>

        @if ($pola->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-10 text-center">
                <div class="text-xl font-bold">Belum ada jadwal shift</div>
                <p class="mt-1 text-sm text-muted">Admin belum menetapkan shift untuk Anda.</p>
            </div>
        @else
            <div class="grid grid-cols-[repeat(auto-fit,minmax(9rem,1fr))] gap-3">
                @foreach ($hariList as $nomor => $nama)
                    @php
                        $shiftHari = $pola->get($nomor, collect());
                        $hariIniIni = $nomor === $hariIni->dayOfWeekIso;
                    @endphp
                    <div wire:key="hari-{{ $nomor }}" class="flex flex-col overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper {{ $hariIniIni ? 'outline-3 outline-yellow' : '' }}">
                        <div class="border-b-[1.5px] border-ink px-3 py-2 {{ $hariIniIni ? 'bg-yellow' : 'bg-cream' }}">
                            <div class="font-bold"><x-ikon :e="$ikonHari[$nomor]" /> {{ $nama }}</div>
                            @if ($hariIniIni)<div class="text-xs font-semibold">(hari ini)</div>@endif
                        </div>
                        <div class="grid flex-1 content-start gap-2 p-3">
                            @forelse ($shiftHari as $s)
                                <div wire:key="pola-{{ $s->id }}" class="rounded-ta border-[1.5px] border-ink px-3 py-2 {{ $palet[($s->kelompok_usia_id ?? 0) % count($palet)] }}">
                                    <div class="whitespace-nowrap text-sm font-bold">{{ $jam($s) }}</div>
                                    <div class="text-xs">{{ $s->kelompokUsia?->nama ?? '—' }}</div>
                                </div>
                            @empty
                                <p class="py-2 text-center text-sm text-muted">Libur</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
