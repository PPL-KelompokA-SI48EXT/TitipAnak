@php
    // Satu warna pastel per kelompok usia (urut dari yang termuda), sama dengan jadwal shift.
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
    $warnaJenis = ['makan' => 'bg-yellow', 'bermain' => 'bg-blue', 'belajar' => 'bg-lavender', 'tidur' => 'bg-mint', 'lainnya' => 'bg-cream'];
    $ikonJenis = ['makan' => '🍽️', 'bermain' => '🧩', 'belajar' => '📚', 'tidur' => '😴', 'lainnya' => '⭐'];
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

    @if (session('galat'))
        <div role="alert" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-coral px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="⚠" /></span>
            <span>{{ session('galat') }}</span>
        </div>
    @endif

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="✅" /></span>
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

    @if ($tampil > 0)
        <ul class="-mt-2 flex flex-wrap items-center gap-2 text-xs" aria-label="Keterangan warna jenis kegiatan">
            @foreach ($jenisList as $kode => $label)
                <li class="inline-flex items-center gap-1.5 rounded-full border border-ink px-3 py-1 font-semibold {{ $warnaJenis[$kode] ?? 'bg-cream' }}"><x-ikon :e="$ikonJenis[$kode] ?? '⭐'" /> {{ $label }}</li>
            @endforeach
        </ul>
    @endif

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
                    <ol class="grid gap-3 p-4">
                        @foreach ($daftar as $g)
                            <li wire:key="kegiatan-{{ $g->id }}" class="grid gap-2 sm:grid-cols-[8.5rem_1fr] sm:gap-4">
                                <div class="sm:pt-3">
                                    <div class="whitespace-nowrap text-sm font-bold">{{ $jam($g) }}</div>
                                    <div class="text-xs text-muted">{{ $g->labelHari() }}</div>
                                </div>
                                <div class="flex flex-wrap items-start justify-between gap-3 rounded-ta border-[1.5px] border-ink px-4 py-3 {{ $warnaJenis[$g->jenis_kegiatan] ?? 'bg-cream' }}">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span aria-hidden="true"><x-ikon :e="$ikonJenis[$g->jenis_kegiatan] ?? '⭐'" /></span>
                                            <span class="font-bold">{{ $g->nama_kegiatan }}</span>
                                            <span class="rounded-full border border-ink bg-paper px-3 py-0.5 text-xs font-semibold">{{ $g->labelJenis() }}</span>
                                        </div>
                                        @if ($g->catatan)
                                            <p class="mt-1 text-sm">{{ $g->catatan }}</p>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2 text-sm">
                                        <a href="{{ route('admin.jadwal-kegiatan.ubah', $g) }}" class="rounded-ta border-[1.5px] border-ink bg-paper px-3 py-1 font-semibold transition hover:bg-cream"
                                            aria-label="Ubah kegiatan {{ $g->nama_kegiatan }}">Ubah</a>
                                        <button type="button" wire:click="konfirmasiHapus({{ $g->id }})" class="rounded-ta border-[1.5px] border-ink bg-paper px-3 py-1 font-semibold transition hover:bg-cream"
                                            aria-label="Hapus kegiatan {{ $g->nama_kegiatan }}">Hapus</button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endforeach
    @endif

    @if ($hapusKegiatan)
        <x-konfirmasi-hapus judul="Hapus kegiatan?" tombol="Ya, hapus">
            <p>
                Kegiatan <b>{{ $hapusKegiatan->nama_kegiatan }}</b> untuk <b>{{ $hapusKegiatan->kelompokUsia?->nama ?? '—' }}</b>
                ({{ $hapusKegiatan->labelHari() }}, {{ substr($hapusKegiatan->jam_mulai, 0, 5) }} – {{ substr($hapusKegiatan->jam_selesai, 0, 5) }})
                akan dihapus dari jadwal karena sudah tidak dilakukan.
            </p>
        </x-konfirmasi-hapus>
    @endif
</div>
