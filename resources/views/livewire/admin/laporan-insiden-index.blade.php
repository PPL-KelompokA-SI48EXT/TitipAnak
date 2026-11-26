<div class="grid gap-6">
    {{-- Tajuk Halaman --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Laporan Insiden</h1>
            <p class="mt-1 text-sm text-muted">
                Pantau seluruh laporan insiden anak di daycare dan hapus catatan yang duplikat atau tidak valid.
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-ink bg-yellow px-3 py-1.5 text-xs font-semibold">
            <x-ikon e="🛡️" class="size-4" /> Admin
        </span>
    </div>

    {{-- Notifikasi --}}
    @if (session('sukses'))
        <div class="flex items-center gap-3 rounded-ta border-[1.5px] border-ink bg-mint p-4 text-sm font-medium" role="status">
            <x-ikon e="✅" class="size-5 shrink-0" />
            <span>{{ session('sukses') }}</span>
        </div>
    @endif

    @if (session('galat'))
        <div class="flex items-center gap-3 rounded-ta border-[1.5px] border-ink bg-coral p-4 text-sm font-medium" role="alert">
            <x-ikon e="⚠️" class="size-5 shrink-0" />
            <span>{{ session('galat') }}</span>
        </div>
    @endif

    {{-- Kartu Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-ta border-[1.5px] border-ink bg-coral/45 p-5">
            <div class="text-3xl font-bold leading-none">{{ $totalTampil }}</div>
            <div class="mt-2 text-sm font-bold">Laporan Insiden Tercatat</div>
            <div class="text-xs opacity-80">Sesuai filter yang sedang aktif</div>
        </div>
        <div class="rounded-ta border-[1.5px] border-ink bg-yellow p-5">
            <div class="text-3xl font-bold leading-none">{{ $totalDuplikat }}</div>
            <div class="mt-2 text-sm font-bold">Indikasi Duplikat</div>
            <div class="text-xs opacity-80">Anak, tanggal, dan judul yang sama</div>
        </div>
    </div>

    {{-- Toolbar Filter --}}
    <div class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-cream p-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="sm:col-span-1">
                <input type="search" wire:model.live.debounce.300ms="q"
                    placeholder="Cari anak, judul, atau pengasuh…"
                    class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm focus-visible:outline-3 focus-visible:outline-yellow">
            </div>

            <div>
                <select wire:model.live="kelompokId" aria-label="Filter kelompok usia"
                    class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm font-medium focus-visible:outline-3 focus-visible:outline-yellow">
                    <option value="">Semua Kelompok Usia</option>
                    @foreach ($kelompokList as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="tanggal" aria-label="Filter tanggal kejadian"
                    class="min-w-0 flex-1 rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm font-medium focus-visible:outline-3 focus-visible:outline-yellow">
                <button type="button" wire:click="filterHariIni"
                    class="shrink-0 rounded-ta border-[1.5px] border-ink bg-paper px-3 py-2 text-xs font-semibold hover:bg-yellow">
                    Hari Ini
                </button>
            </div>
        </div>

        @if ($q !== '' || $kelompokId !== '' || $tanggal !== '')
            <div class="flex justify-end">
                <button type="button" wire:click="resetFilter"
                    class="text-xs font-semibold underline hover:opacity-75">
                    Reset semua filter
                </button>
            </div>
        @endif
    </div>

    {{-- Daftar Laporan Insiden --}}
    @if ($daftarInsiden->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
            <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-mint text-2xl">
                <x-ikon e="📋" class="size-7" />
            </div>
            <h2 class="text-lg font-bold">Tidak ada laporan insiden</h2>
            <p class="mt-1 text-sm text-muted">
                Belum ada catatan insiden yang sesuai dengan filter pencarian saat ini.
            </p>
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($daftarInsiden as $insiden)
                <article wire:key="admin-insiden-{{ $insiden->id }}"
                    class="rounded-ta border-[1.5px] border-ink bg-paper p-5 {{ $insiden->potensi_duplikat ? 'ring-2 ring-yellow' : '' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-coral text-base font-bold">
                                {{ mb_strtoupper(mb_substr($insiden->anak?->nama_lengkap ?? 'A', 0, 1)) }}
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-bold">{{ $insiden->judul }}</h3>
                                    <span class="rounded-full border border-ink bg-coral px-2.5 py-0.5 text-xs font-semibold">
                                        {{ $insiden->waktu_kejadian->translatedFormat('d M Y · H:i') }}
                                    </span>
                                    <span class="rounded-full border border-ink bg-cream px-2.5 py-0.5 text-xs font-semibold">
                                        {{ $insiden->anak?->nama_lengkap ?? '—' }} ({{ $insiden->anak?->kelompokUsia?->nama ?? '—' }})
                                    </span>
                                    @if ($insiden->potensi_duplikat)
                                        <span class="rounded-full border border-ink bg-yellow px-2.5 py-0.5 text-xs font-bold">
                                            Potensi Duplikat
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-muted">
                                    Dicatat oleh: <strong class="text-ink">{{ $insiden->pengasuh?->nama ?? 'Pengasuh' }}</strong>
                                    · ID #{{ $insiden->id }}
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="konfirmasiHapus({{ $insiden->id }})"
                            class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-coral px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5">
                            <x-ikon e="🗑️" class="size-4" /> Hapus Laporan
                        </button>
                    </div>

                    <div class="mt-4 grid gap-3 border-t border-ink/20 pt-4 text-sm sm:grid-cols-2">
                        <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                            <div class="text-xs font-semibold text-muted">Kronologi Kejadian</div>
                            <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $insiden->deskripsi }}</p>
                        </div>

                        <div class="rounded-lg border border-ink/30 bg-mint/35 p-3.5">
                            <div class="text-xs font-semibold text-muted">Tindakan Penanganan</div>
                            <p class="mt-1 whitespace-pre-line leading-relaxed">
                                {{ $insiden->tindakan ?: 'Belum ada catatan tindakan lanjutan.' }}
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- Modal Konfirmasi Hapus Laporan Insiden --}}
    @if ($insidenAkanDihapus)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/55 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-hapus-insiden-judul">
            <div class="w-full max-w-md overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                <div class="flex items-center justify-between gap-3 border-b-[1.5px] border-ink bg-coral px-5 py-4">
                    <h2 id="modal-hapus-insiden-judul" class="text-lg font-bold">Hapus Laporan Insiden?</h2>
                    <button type="button" wire:click="batalHapus" aria-label="Tutup"
                        class="grid size-9 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-sm font-bold hover:bg-cream">
                        ✕
                    </button>
                </div>

                <div class="grid gap-3 p-5 text-sm">
                    <p>
                        Anda yakin ingin menghapus laporan insiden
                        <strong>"{{ $insidenAkanDihapus->judul }}"</strong>
                        untuk anak <strong>{{ $insidenAkanDihapus->anak?->nama_lengkap ?? '—' }}</strong>?
                    </p>
                    <div class="rounded-lg border border-ink/30 bg-cream p-3 text-xs text-muted">
                        Waktu kejadian: <strong class="text-ink">{{ $insidenAkanDihapus->waktu_kejadian->translatedFormat('d M Y, H:i') }}</strong><br>
                        Dicatat oleh: <strong class="text-ink">{{ $insidenAkanDihapus->pengasuh?->nama ?? 'Pengasuh' }}</strong>
                    </div>
                    <p class="text-xs font-semibold text-[#B02E12]">
                        Tindakan ini permanen dan laporan tidak lagi tampil di halaman Pengasuh maupun Orang Tua.
                    </p>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t-[1.5px] border-ink bg-cream px-5 py-4">
                    <button type="button" wire:click="batalHapus"
                        class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold hover:bg-cream">
                        Batal
                    </button>
                    <button type="button" wire:click="hapusInsiden" wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-coral px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                        <span wire:loading.remove wire:target="hapusInsiden">Ya, Hapus Permanen</span>
                        <span wire:loading wire:target="hapusInsiden">Menghapus…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>