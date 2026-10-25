@php
    $input = 'rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $adaFilter = $q !== '' || $kelompok !== '' || ($mode === 'daftar' && $hari !== '');
    $hariIni = today();

    // Satu warna pastel per kelompok usia (urut dari yang termuda).
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
    $warna = $kelompokList->values()->mapWithKeys(fn ($k, $i) => [$k->id => $palet[$i % count($palet)]]);

    $hariSingkat = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
    $bln = fn ($d) => substr($bulanList[$d->month], 0, 3);

    $judulKalender = $mode === 'bulan'
        ? $bulanList[$acuan->month].' '.$acuan->year
        : $pekan->first()->day.' '.($pekan->first()->month !== $pekan->last()->month ? $bln($pekan->first()).' ' : '')
            .'– '.$pekan->last()->day.' '.$bln($pekan->last()).' '.$pekan->last()->year;

    $jam = fn ($j) => substr($j->jam_mulai, 0, 5).' – '.substr($j->jam_selesai, 0, 5);
    $namaDepan = fn ($j) => explode(' ', $j->pengasuh?->nama ?? '—')[0];
@endphp

<div class="grid gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold leading-tight">Jadwal Shift</h1>
            <p class="text-muted">Pantau penugasan pengasuh ke setiap kelompok usia.</p>
        </div>
        <a href="{{ route('admin.jadwal-shift.buat') }}"
            class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
            Buat jadwal baru
        </a>
    </div>

    {{-- Pilihan tampilan --}}
    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Pilih tampilan">
        @foreach (['minggu' => 'Minggu', 'bulan' => 'Bulan', 'daftar' => 'Daftar'] as $kode => $label)
            <button type="button" wire:click="setMode('{{ $kode }}')" aria-pressed="{{ $mode === $kode ? 'true' : 'false' }}"
                class="rounded-full border-[1.5px] border-ink px-4 py-2 text-sm font-semibold transition {{ $mode === $kode ? 'bg-ink text-paper' : 'bg-paper hover:bg-cream' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Pencarian & filter --}}
    <div class="flex flex-wrap items-center gap-3">
        <label class="relative min-w-56 flex-1">
            <span class="sr-only">Cari pengasuh</span>
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true">🔍</span>
            <input type="search" wire:model.live.debounce.300ms="q" placeholder="Cari nama pengasuh…" autocomplete="off"
                class="{{ $input }} w-full pl-10">
        </label>

        <select wire:model.live="kelompok" aria-label="Filter kelompok usia" class="{{ $input }}">
            <option value="">Semua kelompok usia</option>
            @foreach ($kelompokList as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
        </select>

        @if ($mode === 'daftar')
            <select wire:model.live="hari" aria-label="Filter hari" class="{{ $input }}">
                <option value="">Semua hari</option>
                @foreach ($hariList as $nomor => $nama)
                    <option value="{{ $nomor }}">{{ $nama }}</option>
                @endforeach
            </select>
        @endif

        @if ($adaFilter)
            <button type="button" wire:click="resetFilter"
                class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2.5 text-sm font-semibold transition hover:bg-cream">Reset filter</button>
        @endif
    </div>

    {{-- Legenda warna kelompok usia --}}
    @if ($mode !== 'daftar')
        <div class="-mt-2 flex flex-wrap items-center gap-2 text-xs" aria-label="Legenda kelompok usia">
            @foreach ($kelompokList as $k)
                <span class="rounded-full border border-ink px-3 py-1 font-semibold {{ $warna[$k->id] }}">{{ $k->nama }}</span>
            @endforeach
        </div>
    @endif

    {{-- Navigasi kalender --}}
    @if ($mode !== 'daftar')
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="pindah(-1)" aria-label="{{ $mode === 'bulan' ? 'Bulan sebelumnya' : 'Minggu sebelumnya' }}"
                    class="grid size-10 place-items-center rounded-ta border-[1.5px] border-ink bg-paper text-lg font-bold transition hover:bg-cream">‹</button>
                <button type="button" wire:click="pindah(1)" aria-label="{{ $mode === 'bulan' ? 'Bulan berikutnya' : 'Minggu berikutnya' }}"
                    class="grid size-10 place-items-center rounded-ta border-[1.5px] border-ink bg-paper text-lg font-bold transition hover:bg-cream">›</button>
                <button type="button" wire:click="hariIni"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold transition hover:bg-cream">Hari ini</button>
            </div>
            <h2 class="text-xl font-bold">{{ $judulKalender }}</h2>
        </div>
    @endif

    <p class="-mt-3 text-sm text-muted" aria-live="polite">Menampilkan {{ $jadwal->count() }} dari {{ $semua }} jadwal</p>

    @if ($jadwal->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">{{ $semua === 0 ? 'Belum ada jadwal shift' : 'Tidak ada hasil' }}</div>
            <p class="mt-1 text-sm text-muted">
                {{ $semua === 0 ? 'Buat jadwal pertama untuk mulai membagi tugas pengasuh.' : 'Coba kata kunci lain atau reset filter.' }}
            </p>
        </div>

    {{-- ===================== TAMPILAN MINGGU ===================== --}}
    @elseif ($mode === 'minggu')
        <div class="grid overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper md:grid-cols-7">
            @foreach ($pekan as $i => $tgl)
                @php $laHariIni = $tgl->isSameDay($hariIni); @endphp
                <section class="min-w-0 border-ink max-md:border-b-[1.5px] md:border-r-[1.5px] md:last:border-r-0 last:max-md:border-b-0" aria-label="{{ $hariList[$i + 1] }} {{ $tgl->day }} {{ $bulanList[$tgl->month] }}">
                    <header class="flex items-center justify-between gap-2 border-b-[1.5px] border-ink px-3 py-2 md:block md:text-center {{ $laHariIni ? 'bg-yellow' : 'bg-cream' }}">
                        <div class="text-xs font-semibold uppercase tracking-wide">
                            <span class="md:hidden">{{ $hariList[$i + 1] }}</span><span class="hidden md:inline">{{ $hariSingkat[$i + 1] }}</span>
                        </div>
                        <div class="text-2xl font-bold leading-tight">{{ $tgl->day }}</div>
                    </header>

                    <div class="grid min-h-24 content-start gap-2 p-2">
                        @forelse ($perHari->get($i + 1, collect()) as $j)
                            <article wire:key="minggu-{{ $j->id }}-{{ $tgl->toDateString() }}"
                                class="rounded-lg border-[1.5px] border-ink p-2 text-xs {{ $warna[$j->kelompok_usia_id] ?? 'bg-cream' }}">
                                <div class="font-bold">{{ $jam($j) }}</div>
                                <div class="mt-0.5 break-words">{{ $j->pengasuh?->nama ?? '—' }}</div>
                                <div class="mt-1 font-semibold">{{ $j->kelompokUsia?->nama ?? '—' }}</div>
                            </article>
                        @empty
                            <p class="py-4 text-center text-xs text-muted">Tidak ada shift</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

    {{-- ===================== TAMPILAN BULAN ===================== --}}
    @elseif ($mode === 'bulan')
        <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
            <div class="min-w-[44rem]">
                <div class="grid grid-cols-7 border-b-[1.5px] border-ink bg-yellow text-center text-xs font-bold uppercase tracking-wide">
                    @foreach ($hariSingkat as $nama)
                        <div class="px-2 py-2">{{ $nama }}</div>
                    @endforeach
                </div>

                @foreach ($grid as $baris)
                    <div class="grid grid-cols-7 border-ink {{ $loop->last ? '' : 'border-b' }}">
                        @foreach ($baris as $tgl)
                            @php
                                $dalamBulan = $tgl->month === $acuan->month;
                                $shift = $perHari->get($tgl->dayOfWeekIso, collect());
                            @endphp
                            <div class="min-h-28 min-w-0 border-ink p-1.5 {{ $loop->last ? '' : 'border-r' }} {{ $dalamBulan ? 'bg-paper' : 'bg-cream/60' }}">
                                <button type="button" wire:click="pilihTanggal('{{ $tgl->toDateString() }}')"
                                    aria-label="Lihat minggu {{ $tgl->day }} {{ $bulanList[$tgl->month] }}"
                                    class="mb-1 grid size-7 place-items-center rounded-full text-xs font-bold transition hover:bg-cream
                                        {{ $tgl->isSameDay($hariIni) ? 'border-[1.5px] border-ink bg-yellow' : ($dalamBulan ? '' : 'text-muted') }}">{{ $tgl->day }}</button>

                                @foreach ($shift->take(3) as $j)
                                    <div wire:key="bulan-{{ $j->id }}-{{ $tgl->toDateString() }}"
                                        title="{{ $jam($j) }} · {{ $j->pengasuh?->nama }} · {{ $j->kelompokUsia?->nama }}"
                                        class="mb-1 truncate rounded border border-ink px-1.5 py-0.5 text-[11px] font-semibold {{ $warna[$j->kelompok_usia_id] ?? 'bg-cream' }} {{ $dalamBulan ? '' : 'opacity-60' }}">
                                        {{ substr($j->jam_mulai, 0, 5) }} {{ $namaDepan($j) }}
                                    </div>
                                @endforeach

                                @if ($shift->count() > 3)
                                    <button type="button" wire:click="pilihTanggal('{{ $tgl->toDateString() }}')"
                                        class="text-[11px] font-semibold text-muted underline">+{{ $shift->count() - 3 }} lagi</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

    {{-- ===================== TAMPILAN DAFTAR ===================== --}}
    @else
        <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-yellow text-left">
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Hari</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Jam</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Pengasuh</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Kelompok usia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jadwal as $j)
                        <tr wire:key="shift-{{ $j->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $hariList[$j->hari] ?? $j->hari }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $jam($j) }}</td>
                            <td class="px-4 py-3">{{ $j->pengasuh?->nama ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full border border-ink px-3 py-1 text-xs font-semibold {{ $warna[$j->kelompok_usia_id] ?? 'bg-cream' }}">{{ $j->kelompokUsia?->nama ?? '—' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
