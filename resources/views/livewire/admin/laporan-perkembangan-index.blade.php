<div class="grid gap-6">
    {{-- Tajuk Halaman --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Laporan Perkembangan Anak</h1>
            <p class="mt-1 text-sm text-muted">
                Awasi kualitas penilaian perkembangan anak dari pengasuh dan hapus laporan yang tidak valid.
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

    {{-- Kartu Ringkasan Supervisi --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-ta border-[1.5px] border-ink bg-blue p-5">
            <div class="text-3xl font-bold leading-none">{{ $totalSemua }}</div>
            <div class="mt-2 text-sm font-bold">Total Laporan</div>
            <div class="text-xs opacity-80">Sesuai filter periode &amp; kelompok</div>
        </div>
        <div class="rounded-ta border-[1.5px] border-ink bg-mint p-5">
            <div class="text-3xl font-bold leading-none">{{ $totalLengkap }}</div>
            <div class="mt-2 text-sm font-bold">Aspek Lengkap</div>
            <div class="text-xs opacity-80">Keempat aspek utama terisi</div>
        </div>
        <div class="rounded-ta border-[1.5px] border-ink bg-yellow p-5">
            <div class="text-3xl font-bold leading-none">{{ $totalKurangLengkap }}</div>
            <div class="mt-2 text-sm font-bold">Aspek Belum Lengkap</div>
            <div class="text-xs opacity-80">Perlu diperiksa / tidak valid</div>
        </div>
    </div>

    {{-- Toolbar Filter --}}
    <div class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-cream p-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <input type="search" wire:model.live.debounce.300ms="q"
                    placeholder="Cari nama anak atau pengasuh…"
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
                <input type="month" wire:model.live="periode" aria-label="Filter periode"
                    class="min-w-0 flex-1 rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm font-medium focus-visible:outline-3 focus-visible:outline-yellow">
                <button type="button" wire:click="filterPeriodeIni"
                    class="shrink-0 rounded-ta border-[1.5px] border-ink bg-paper px-3 py-2 text-xs font-semibold hover:bg-yellow">
                    Periode Ini
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="pilihKelengkapan('')"
                    class="rounded-full border border-ink px-3.5 py-1.5 text-xs font-semibold transition {{ $statusKelengkapan === '' ? 'bg-yellow border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                    Semua ({{ $totalSemua }})
                </button>
                <button type="button" wire:click="pilihKelengkapan('lengkap')"
                    class="rounded-full border border-ink px-3.5 py-1.5 text-xs font-semibold transition {{ $statusKelengkapan === 'lengkap' ? 'bg-yellow border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                    Aspek Lengkap ({{ $totalLengkap }})
                </button>
                <button type="button" wire:click="pilihKelengkapan('kurang')"
                    class="rounded-full border border-ink px-3.5 py-1.5 text-xs font-semibold transition {{ $statusKelengkapan === 'kurang' ? 'bg-yellow border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                    Belum Lengkap ({{ $totalKurangLengkap }})
                </button>
            </div>

            @if ($q !== '' || $kelompokId !== '' || $periode !== '' || $statusKelengkapan !== '')
                <button type="button" wire:click="resetFilter"
                    class="text-xs font-semibold underline hover:opacity-75">
                    Reset semua filter
                </button>
            @endif
        </div>
    </div>

    {{-- Daftar Laporan Perkembangan --}}
    @if ($daftarPenilaian->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
            <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-cream text-2xl">
                <x-ikon e="📈" class="size-7" />
            </div>
            <h2 class="text-lg font-bold">Tidak ada data laporan perkembangan</h2>
            <p class="mt-1 text-sm text-muted">
                Belum ada penilaian perkembangan anak yang sesuai dengan filter saat ini.
            </p>
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($daftarPenilaian as $penilaian)
                <article wire:key="admin-perkembangan-{{ $penilaian->id }}"
                    class="rounded-ta border-[1.5px] border-ink bg-paper p-5 {{ $penilaian->kurang_lengkap ? 'ring-2 ring-yellow' : '' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-lavender text-base font-bold">
                                {{ mb_strtoupper(mb_substr($penilaian->anak?->nama_lengkap ?? 'A', 0, 1)) }}
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-bold">{{ $penilaian->anak?->nama_lengkap ?? '—' }}</h3>
                                    <span class="rounded-full border border-ink bg-mint px-2.5 py-0.5 text-xs font-semibold">
                                        {{ $penilaian->periode_bulan->translatedFormat('F Y') }}
                                    </span>
                                    <span class="rounded-full border border-ink bg-cream px-2.5 py-0.5 text-xs font-semibold">
                                        {{ $penilaian->anak?->kelompokUsia?->nama ?? '—' }}
                                    </span>
                                    @if ($penilaian->kurang_lengkap)
                                        <span class="rounded-full border border-ink bg-yellow px-2.5 py-0.5 text-xs font-bold">
                                            Aspek Belum Lengkap
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-muted">
                                    Dinilai oleh: <strong class="text-ink">{{ $penilaian->pengasuh?->nama ?? 'Pengasuh' }}</strong>
                                    · Diperbarui {{ $penilaian->updated_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                                    · ID #{{ $penilaian->id }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.laporan-perkembangan.unduh', $penilaian) }}"
                                class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5 hover:bg-cream">
                                <x-ikon e="⬇️" class="size-4" /> Unduh PDF
                            </a>
                            <button type="button" wire:click="konfirmasiHapus({{ $penilaian->id }})"
                                class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-coral px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5">
                                <x-ikon e="🗑️" class="size-4" /> Hapus Penilaian
                            </button>
                        </div>
                    </div>

                    {{-- Detail 4 Aspek & Catatan Umum --}}
                    <div class="mt-4 grid gap-3 border-t border-ink/20 pt-4 text-sm sm:grid-cols-2">
                        <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                            <div class="text-xs font-bold text-muted">1. Perkembangan Motorik</div>
                            <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_motorik ?: '— (Kosong)' }}</p>
                        </div>
                        <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                            <div class="text-xs font-bold text-muted">2. Perkembangan Bahasa</div>
                            <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_bahasa ?: '— (Kosong)' }}</p>
                        </div>
                        <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                            <div class="text-xs font-bold text-muted">3. Sosial &amp; Emosional</div>
                            <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_sosial ?: '— (Kosong)' }}</p>
                        </div>
                        <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                            <div class="text-xs font-bold text-muted">4. Perkembangan Kognitif</div>
                            <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_kognitif ?: '— (Kosong)' }}</p>
                        </div>

                        @if ($penilaian->catatan_umum)
                            <div class="rounded-lg border border-ink/30 bg-mint/30 p-3.5 sm:col-span-2">
                                <div class="text-xs font-bold text-muted">5. Catatan Umum</div>
                                <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_umum }}</p>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- Modal Konfirmasi Hapus Penilaian Tidak Valid --}}
    @if ($penilaianAkanDihapus)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/55 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-hapus-penilaian-judul">
            <div class="w-full max-w-md overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                <div class="flex items-center justify-between gap-3 border-b-[1.5px] border-ink bg-coral px-5 py-4">
                    <h2 id="modal-hapus-penilaian-judul" class="text-lg font-bold">Hapus Laporan Perkembangan?</h2>
                    <button type="button" wire:click="batalHapus" aria-label="Tutup"
                        class="grid size-9 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-sm font-bold hover:bg-cream">
                        ✕
                    </button>
                </div>

                <div class="grid gap-3 p-5 text-sm">
                    <p>
                        Anda yakin ingin menghapus laporan perkembangan untuk anak
                        <strong>{{ $penilaianAkanDihapus->anak?->nama_lengkap ?? '—' }}</strong>
                        pada periode <strong>{{ $penilaianAkanDihapus->periode_bulan->translatedFormat('F Y') }}</strong>?
                    </p>
                    <div class="rounded-lg border border-ink/30 bg-cream p-3 text-xs text-muted">
                        Pengasuh penilai: <strong class="text-ink">{{ $penilaianAkanDihapus->pengasuh?->nama ?? 'Pengasuh' }}</strong><br>
                        ID Laporan: <strong class="text-ink">#{{ $penilaianAkanDihapus->id }}</strong>
                    </div>
                    <p class="text-xs font-semibold text-[#B02E12]">
                        Data yang dihapus bersifat permanen dan tidak akan tampil lagi di halaman Orang Tua maupun Pengasuh.
                    </p>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t-[1.5px] border-ink bg-cream px-5 py-4">
                    <button type="button" wire:click="batalHapus"
                        class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold hover:bg-cream">
                        Batal
                    </button>
                    <button type="button" wire:click="hapusPenilaian" wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-coral px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                        <span wire:loading.remove wire:target="hapusPenilaian">Ya, Hapus Permanen</span>
                        <span wire:loading wire:target="hapusPenilaian">Menghapus…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>