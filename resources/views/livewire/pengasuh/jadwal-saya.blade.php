@php
    $jam = fn ($j) => substr($j->jam_mulai, 0, 5).' – '.substr($j->jam_selesai, 0, 5);
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
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
            <article wire:key="hari-ini-{{ $s->id }}" class="grid gap-4 rounded-ta border-[1.5px] border-ink bg-paper p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-lg font-bold">{{ $s->kelompokUsia?->nama ?? '—' }}</div>
                        <div class="text-sm text-muted">Shift {{ $jam($s) }}</div>
                    </div>
                    <span class="rounded-full border border-ink bg-yellow px-3 py-1 text-sm font-semibold">{{ $anak->count() }} anak</span>
                </div>

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
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-yellow text-left">
                            <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Hari</th>
                            <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Jam</th>
                            <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Kelompok usia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hariList as $nomor => $nama)
                            @foreach ($pola->get($nomor, collect()) as $s)
                                <tr wire:key="pola-{{ $s->id }}" class="border-b border-ink last:border-b-0 {{ $nomor === $hariIni->dayOfWeekIso ? 'bg-yellow/40' : '' }}">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $nama }}@if ($nomor === $hariIni->dayOfWeekIso) <span class="ml-1 text-xs text-muted">(hari ini)</span>@endif</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $jam($s) }}</td>
                                    <td class="px-4 py-3">{{ $s->kelompokUsia?->nama ?? '—' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
