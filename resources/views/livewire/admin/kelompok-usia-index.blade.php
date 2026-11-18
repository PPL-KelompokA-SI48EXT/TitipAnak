
@php
    $formatUsia = fn ($bulan) => $bulan . ' bulan';
@endphp

<div class="grid gap-6">
    {{-- Judul halaman --}}
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-4xl font-bold leading-tight">Kelompok Usia</h1>
            <p class="text-muted">Daftar kelompok usia anak yang terdaftar di daycare.</p>
        </div>

        <a href="{{ route('admin.kelompok-usia.buat') }}"
            class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
            + Tambah Kelompok Usia
        </a>
    </div>

    {{-- Ringkasan jumlah data --}}
    <div class="flex items-center gap-3 rounded-ta border-[1.5px] border-ink bg-cream p-5">
        <div class="flex size-12 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow text-2xl">
            <x-ikon e="🧒" />
        </div>
        <div>
            <p class="text-sm text-muted">Total kelompok usia</p>
            <p class="text-2xl font-bold">{{ $total }} kelompok</p>
        </div>
    </div>

    {{-- Tabel kelompok usia --}}
    <div class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] border-collapse text-left text-sm">
                <thead class="bg-yellow">
                    <tr class="border-b-[1.5px] border-ink">
                        <th class="px-4 py-3 font-bold">No.</th>
                        <th class="px-4 py-3 font-bold">Nama Kelompok</th>
                        <th class="px-4 py-3 font-bold">Usia Minimum</th>
                        <th class="px-4 py-3 font-bold">Usia Maksimum</th>
                        <th class="px-4 py-3 font-bold">Kapasitas</th>
                        <th class="px-4 py-3 font-bold">Deskripsi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($kelompok as $index => $item)
                        <tr class="{{ $loop->even ? 'bg-cream' : 'bg-paper' }} border-b border-ink/20">
                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $item->nama }}</td>
                            <td class="px-4 py-3">{{ $formatUsia($item->usia_min_bulan) }}</td>
                            <td class="px-4 py-3">{{ $formatUsia($item->usia_maks_bulan) }}</td>
                            <td class="px-4 py-3">{{ $item->kapasitas }} anak</td>
                            <td class="px-4 py-3">
                                {{ $item->deskripsi ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="grid justify-items-center gap-2">
                                    <span class="text-4xl" aria-hidden="true"><x-ikon e="🧸" /></span>
                                    <p class="font-bold">Belum ada kelompok usia</p>
                                    <p class="text-sm text-muted">
                                        Silakan tambahkan kelompok usia terlebih dahulu.
                                    </p>
                                    <a href="{{ route('admin.kelompok-usia.buat') }}"
                                        class="mt-2 font-semibold text-link underline">
                                        Tambah kelompok usia
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
