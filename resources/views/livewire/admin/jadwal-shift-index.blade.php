@php
    $input = 'rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $adaFilter = $q !== '' || $kelompok !== '' || $hari !== '';
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

        <select wire:model.live="hari" aria-label="Filter hari" class="{{ $input }}">
            <option value="">Semua hari</option>
            @foreach ($hariList as $nomor => $nama)
                <option value="{{ $nomor }}">{{ $nama }}</option>
            @endforeach
        </select>

        @if ($adaFilter)
            <button type="button" wire:click="resetFilter"
                class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2.5 text-sm font-semibold transition hover:bg-cream">Reset filter</button>
        @endif
    </div>

    <p class="-mt-3 text-sm text-muted" aria-live="polite">Menampilkan {{ $jadwal->count() }} dari {{ $semua }} jadwal</p>

    @if ($jadwal->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">{{ $semua === 0 ? 'Belum ada jadwal shift' : 'Tidak ada hasil' }}</div>
            <p class="mt-1 text-sm text-muted">
                {{ $semua === 0 ? 'Buat jadwal pertama untuk mulai membagi tugas pengasuh.' : 'Coba kata kunci lain atau reset filter.' }}
            </p>
        </div>
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
                            <td class="whitespace-nowrap px-4 py-3">{{ substr($j->jam_mulai, 0, 5) }} – {{ substr($j->jam_selesai, 0, 5) }}</td>
                            <td class="px-4 py-3">{{ $j->pengasuh?->nama ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full border border-ink bg-lavender px-3 py-1 text-xs font-semibold">{{ $j->kelompokUsia?->nama ?? '—' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
