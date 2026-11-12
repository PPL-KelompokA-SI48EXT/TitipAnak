@php
    $label = ['belum' => 'Belum datang', 'hadir' => 'Hadir', 'pulang' => 'Sudah pulang'];
    $warna = ['belum' => 'bg-cream', 'hadir' => 'bg-mint', 'pulang' => 'bg-blue'];
    $input = 'rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $adaFilter = $q !== '' || $statusAktif !== '';
@endphp

<div class="grid gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold leading-tight">Kehadiran Hari Ini</h1>
            <p class="text-muted">Daftar kehadiran anak di kelompok Anda · {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}</p>
        </div>
        <a href="{{ route('pengasuh.presensi') }}"
            class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
            Catat presensi
        </a>
    </div>

    @if ($semua > 0)
        {{-- Ringkasan status (klik untuk menyaring) --}}
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter status kehadiran">
            @foreach ($label as $kode => $nama)
                <button type="button" wire:click="pilihStatus('{{ $kode }}')" aria-pressed="{{ $statusAktif === $kode ? 'true' : 'false' }}"
                    class="rounded-full border-[1.5px] border-ink px-4 py-2 text-sm font-semibold transition {{ $statusAktif === $kode ? 'bg-ink text-paper' : $warna[$kode].' hover:-translate-y-0.5' }}">
                    {{ $nama }} · {{ $jumlah[$kode] }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label class="relative min-w-56 flex-1">
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

        <p class="-mt-3 text-sm text-muted" aria-live="polite">Menampilkan {{ $anak->count() }} dari {{ $semua }} anak</p>
    @endif

    @if ($semua === 0)
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada anak untuk hari ini</div>
            <p class="mt-1 text-sm text-muted">Anda tidak punya shift hari ini, atau kelompok usia Anda belum punya anak aktif.</p>
        </div>
    @elseif ($anak->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada hasil</div>
            <p class="mt-1 text-sm text-muted">Coba kata kunci lain atau reset filter.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-yellow text-left">
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Anak</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Kelompok usia</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Status</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Jam datang</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Kondisi</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Jam pulang</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($anak as $a)
                        @php $p = $a->presensi_hari_ini; @endphp
                        <tr wire:key="anak-{{ $a->id }}" class="border-b border-ink last:border-b-0 even:bg-cream/50">
                            <td class="px-4 py-3 font-semibold">{{ $a->nama_lengkap }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $a->kelompokUsia?->nama ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full border border-ink px-3 py-1 text-xs font-semibold {{ $warna[$a->status_hari_ini] }}">{{ $label[$a->status_hari_ini] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $p?->check_in_pada->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $p?->kondisi_check_in ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $p?->check_out_pada?->format('H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
