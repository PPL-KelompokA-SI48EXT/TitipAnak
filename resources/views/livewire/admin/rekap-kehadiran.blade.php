@php
    $input = 'rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $fmt = fn ($t) => $t->format('d/m/Y');
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Rekap Kehadiran</h1>
        <p class="text-muted">Riwayat check-in dan check-out anak · {{ $fmt($awal) }} – {{ $fmt($akhir) }}</p>
    </div>

    {{-- Filter --}}
    <div class="flex flex-wrap items-end gap-3">
        <div class="grid gap-1">
            <label for="dari" class="text-xs font-semibold">Dari tanggal</label>
            <input id="dari" type="date" wire:model.live="dari" class="{{ $input }}">
        </div>
        <div class="grid gap-1">
            <label for="sampai" class="text-xs font-semibold">Sampai tanggal</label>
            <input id="sampai" type="date" wire:model.live="sampai" class="{{ $input }}">
        </div>

        <select wire:model.live="kelompok" aria-label="Filter kelompok usia" class="{{ $input }}">
            <option value="">Semua kelompok usia</option>
            @foreach ($kelompokList as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
        </select>

        <label class="relative min-w-52 flex-1">
            <span class="sr-only">Cari nama anak</span>
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true">🔍</span>
            <input type="search" wire:model.live.debounce.300ms="q" placeholder="Cari nama anak…" autocomplete="off"
                class="{{ $input }} w-full pl-10">
        </label>

        @if ($adaFilter)
            <button type="button" wire:click="resetFilter"
                class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2.5 text-sm font-semibold transition hover:bg-cream">Reset filter</button>
        @endif
    </div>

    @if ($terbalik)
        <div role="alert" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-coral px-4 py-3 text-sm">
            <span aria-hidden="true">⚠️</span>
            <span>Tanggal mulai lebih akhir dari tanggal selesai, jadi tidak ada data yang cocok. Periksa rentang tanggal.</span>
        </div>
    @endif

    <p class="-mt-3 text-sm text-muted" aria-live="polite">{{ $baris->total() }} catatan kehadiran</p>

    @if ($baris->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada data kehadiran</div>
            <p class="mt-1 text-sm text-muted">Belum ada catatan pada rentang ini. Coba ubah tanggal atau reset filter.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-yellow text-left">
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Tanggal</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Anak</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Kelompok usia</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Datang</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Kondisi</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Pulang</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Penjemput</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baris as $k)
                        <tr wire:key="kehadiran-{{ $k->id }}" class="border-b border-ink last:border-b-0 even:bg-cream/50">
                            <td class="whitespace-nowrap px-4 py-3">{{ $fmt($k->tanggal) }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $k->anak?->nama_lengkap ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $k->anak?->kelompokUsia?->nama ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $k->check_in_pada->format('H:i') }}</td>
                            <td class="px-4 py-3">{{ $k->kondisi_check_in ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($k->check_out_pada)
                                    {{ $k->check_out_pada->format('H:i') }}
                                @else
                                    <span class="rounded-full border border-ink bg-mint px-3 py-0.5 text-xs font-semibold">Belum pulang</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $k->nama_penjemput ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $baris->links() }}
    @endif
</div>
