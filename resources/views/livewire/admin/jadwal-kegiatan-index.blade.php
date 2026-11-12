@php
    // Satu warna pastel per kelompok usia (urut dari yang termuda), sama dengan jadwal shift.
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
    $jam = fn ($k) => substr($k->jam_mulai, 0, 5).' – '.substr($k->jam_selesai, 0, 5);
    $input = 'rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $adaFilter = $kelompok !== '' || $hari !== '' || $jenis !== '';
@endphp

<div class="grid gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold leading-tight">Jadwal Kegiatan</h1>
            <p class="text-muted">Rutinitas harian setiap kelompok usia.</p>
        </div>
        <a href="{{ route('admin.jadwal-kegiatan.buat') }}"
            class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
            Buat kegiatan baru
        </a>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true">✅</span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    {{-- Filter --}}
    <div class="flex flex-wrap items-center gap-3">
        <select wire:model.live="kelompok" aria-label="Filter kelompok usia" class="{{ $input }}">
            <option value="">Semua kelompok usia</option>
            @foreach ($semuaKelompok as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
        </select>

        <select wire:model.live="hari" aria-label="Filter hari" class="{{ $input }}">
            <option value="">Semua hari</option>
            @foreach ($hariList as $nomor => $nama)
                <option value="{{ $nomor }}">{{ $nama }}</option>
            @endforeach
        </select>

        <select wire:model.live="jenis" aria-label="Filter jenis kegiatan" class="{{ $input }}">
            <option value="">Semua jenis</option>
            @foreach ($jenisList as $kode => $label)
                <option value="{{ $kode }}">{{ $label }}</option>
            @endforeach
        </select>

        @if ($adaFilter)
            <button type="button" wire:click="resetFilter"
                class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2.5 text-sm font-semibold transition hover:bg-cream">Reset filter</button>
        @endif
    </div>

    <p class="-mt-3 text-sm text-muted" aria-live="polite">
        Menampilkan {{ $tampil }} dari {{ $semua }} kegiatan
        @if ($hari !== '')
            pada hari {{ $hariList[$hari] ?? '' }} (termasuk kegiatan setiap hari)
        @endif
    </p>

    @if ($semua === 0)
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Belum ada jadwal kegiatan</div>
            <p class="mt-1 text-sm text-muted">Buat kegiatan pertama untuk menyusun rutinitas anak.</p>
        </div>
    @elseif ($tampil === 0)
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada hasil</div>
            <p class="mt-1 text-sm text-muted">Coba ubah atau reset filter.</p>
        </div>
    @else
        @foreach ($kelompokList as $k)
            @php $daftar = $kegiatan->get($k->id, collect()); @endphp
            <section wire:key="kelompok-{{ $k->id }}" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="kelompok-{{ $k->id }}">
                <header class="flex flex-wrap items-center justify-between gap-2 border-b-[1.5px] border-ink px-4 py-3 {{ $palet[$loop->index % count($palet)] }}">
                    <h2 id="kelompok-{{ $k->id }}" class="text-lg font-bold">{{ $k->nama }}</h2>
                    <span class="text-sm">{{ $k->usia_min_bulan }}–{{ $k->usia_maks_bulan }} bulan · {{ $daftar->count() }} kegiatan</span>
                </header>

                @if ($daftar->isEmpty())
                    <p class="px-4 py-6 text-center text-sm text-muted">{{ $adaFilter ? 'Tidak ada kegiatan yang cocok dengan filter.' : 'Belum ada kegiatan untuk kelompok usia ini.' }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-cream text-left">
                                    <th class="whitespace-nowrap border-b border-ink px-4 py-2 font-bold">Hari</th>
                                    <th class="whitespace-nowrap border-b border-ink px-4 py-2 font-bold">Jam</th>
                                    <th class="whitespace-nowrap border-b border-ink px-4 py-2 font-bold">Kegiatan</th>
                                    <th class="whitespace-nowrap border-b border-ink px-4 py-2 font-bold">Jenis</th>
                                    <th class="whitespace-nowrap border-b border-ink px-4 py-2 font-bold">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($daftar as $g)
                                    <tr wire:key="kegiatan-{{ $g->id }}" class="border-b border-ink last:border-b-0">
                                        <td class="whitespace-nowrap px-4 py-2 font-semibold">{{ $g->labelHari() }}</td>
                                        <td class="whitespace-nowrap px-4 py-2">{{ $jam($g) }}</td>
                                        <td class="px-4 py-2">{{ $g->nama_kegiatan }}</td>
                                        <td class="px-4 py-2"><span class="rounded-full border border-ink bg-cream px-3 py-0.5 text-xs font-semibold">{{ $g->labelJenis() }}</span></td>
                                        <td class="px-4 py-2 text-muted">{{ $g->catatan ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endforeach
    @endif
</div>
