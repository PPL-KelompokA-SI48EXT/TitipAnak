<div class="grid gap-6">
    {{-- Tajuk Halaman --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Laporan Harian Anak</h1>
            <p class="mt-1 text-sm text-muted">
                Pantau aktivitas, makan, tidur, dan foto kegiatan buah hati Anda secara langsung.
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-ink bg-lavender px-3 py-1.5 text-xs font-semibold">
            <x-ikon e="📒" class="size-4" /> Orang Tua
        </span>
    </div>

    @if ($daftarAnak->isEmpty())
        {{-- Kondisi Jika Orang Tua Belum Memiliki Anak Aktif --}}
        <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
            <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-cream text-2xl">
                <x-ikon e="🧒" class="size-7" />
            </div>
            <h2 class="text-lg font-bold">Belum ada data anak aktif</h2>
            <p class="mt-1 text-sm text-muted">
                Laporan harian akan tampil setelah pendaftaran anak Anda diverifikasi dan berstatus aktif.
            </p>
        </div>
    @else
        {{-- Pemilih Anak (jika lebih dari 1 anak) & Pemilih Tanggal --}}
        <div class="grid gap-4 rounded-ta border-[1.5px] border-ink bg-cream p-4">
            @if ($daftarAnak->count() > 1)
                <div class="grid gap-1.5">
                    <span class="text-xs font-semibold uppercase tracking-wide text-muted">Pilih Anak</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($daftarAnak as $itemAnak)
                            <button type="button" wire:click="pilihAnak({{ $itemAnak->id }})"
                                class="inline-flex items-center gap-2 rounded-full border border-ink px-4 py-2 text-xs font-semibold transition
                                    {{ $anakTerpilih?->id === $itemAnak->id ? 'border-[1.5px] bg-yellow font-bold' : 'bg-paper hover:bg-cream' }}">
                                <span>{{ $itemAnak->nama_lengkap }}</span>
                                <span class="opacity-70">· {{ $itemAnak->kelompokUsia?->nama ?? '—' }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <label for="filter-tanggal-ortu" class="text-sm font-semibold">Tanggal:</label>
                    <input id="filter-tanggal-ortu" type="date" wire:model.live="tanggal"
                        class="rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm font-medium focus-visible:outline-3 focus-visible:outline-yellow">

                    @if (! $tanggalObjek->isToday())
                        <button type="button" wire:click="lihatHariIni"
                            class="rounded-ta border-[1.5px] border-ink bg-yellow px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5">
                            Kembali ke Hari Ini
                        </button>
                    @endif
                </div>

                @if ($riwayatTanggal->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-semibold text-muted">Riwayat terbaru:</span>
                        @foreach ($riwayatTanggal as $tglRiwayat)
                            <button type="button" wire:click="$set('tanggal', '{{ $tglRiwayat->toDateString() }}')"
                                class="rounded-full border border-ink px-2.5 py-1 text-xs font-semibold transition
                                    {{ $tanggalObjek->isSameDay($tglRiwayat) ? 'bg-mint border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                                {{ $tglRiwayat->isToday() ? 'Hari Ini' : $tglRiwayat->format('d M') }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Kartu Identitas Anak & Status Kehadiran Tanggal Terpilih --}}
        @if ($anakTerpilih)
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-ta border-[1.5px] border-ink bg-paper p-5">
                <div class="flex items-center gap-3.5">
                    <span class="grid size-12 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-lavender text-lg font-bold">
                        {{ mb_strtoupper(mb_substr($anakTerpilih->nama_lengkap, 0, 1)) }}
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold">{{ $anakTerpilih->nama_lengkap }}</h2>
                            <span class="rounded-full border border-ink bg-cream px-2.5 py-0.5 text-xs font-semibold">
                                {{ $anakTerpilih->kelompokUsia?->nama ?? '—' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-muted">
                            {{ $tanggalObjek->translatedFormat('l, d F Y') }}
                            @if ($kehadiran)
                                · Datang pukul <strong>{{ $kehadiran->check_in_pada?->format('H:i') ?? '—' }}</strong>
                                @if ($kehadiran->check_out_pada)
                                    · Pulang pukul <strong>{{ $kehadiran->check_out_pada->format('H:i') }}</strong>
                                @endif
                            @endif
                        </p>
                    </div>
                </div>

                <div>
                    @if ($kehadiran === null)
                        <span class="rounded-full border border-ink bg-cream px-3 py-1 text-xs font-semibold text-muted">
                            Belum Check-in
                        </span>
                    @elseif ($kehadiran->check_out_pada === null)
                        <span class="rounded-full border border-ink bg-mint px-3 py-1 text-xs font-semibold">
                            Sedang di Daycare
                        </span>
                    @else
                        <span class="rounded-full border border-ink bg-blue px-3 py-1 text-xs font-semibold">
                            Sudah Dijemput
                        </span>
                    @endif
                </div>
            </div>

            {{-- Isi Laporan Harian atau Status Menunggu --}}
            @if ($laporan === null)
                <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
                    <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-yellow text-2xl">
                        <x-ikon e="⏳" class="size-7" />
                    </div>
                    <h3 class="text-lg font-bold">Laporan harian belum tersedia</h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-muted">
                        @if ($kehadiran === null)
                            {{ $anakTerpilih->nama_lengkap }} belum tercatat hadir pada {{ $tanggalObjek->translatedFormat('d M Y') }}.
                        @else
                            Pengasuh sedang mendampingi kegiatan {{ $anakTerpilih->nama_lengkap }}. Laporan akan muncul di sini segera setelah dicatat oleh pengasuh.
                        @endif
                    </p>
                </div>
            @else
                <div class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-5 sm:p-6">
                    {{-- Header Info Pencatat --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-ink/20 pb-3">
                        <div class="text-xs text-muted">
                            Dicatat oleh pengasuh: <strong class="text-ink">{{ $laporan->pengasuh?->nama ?? 'Pengasuh Daycare' }}</strong>
                        </div>
                        <div class="text-xs text-muted">
                            Diperbarui pukul {{ $laporan->updated_at?->format('H:i') }}
                        </div>
                    </div>

                    {{-- 4 Kartu Indikator Utama --}}
                    <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-ta border-[1.5px] border-ink bg-yellow/35 p-4">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-muted">
                                <x-ikon e="🍽️" class="size-4" /> Makan
                            </div>
                            <div class="mt-1.5 text-base font-bold">{{ $laporan->labelStatusMakan() }}</div>
                            @if ($laporan->catatan_makan)
                                <p class="mt-1 text-xs leading-relaxed text-ink/85">{{ $laporan->catatan_makan }}</p>
                            @endif
                        </div>

                        <div class="rounded-ta border-[1.5px] border-ink bg-blue/35 p-4">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-muted">
                                <x-ikon e="😴" class="size-4" /> Tidur Siang
                            </div>
                            <div class="mt-1.5 text-base font-bold">
                                {{ $laporan->durasi_tidur_menit !== null ? $laporan->durasi_tidur_menit.' menit' : 'Tidak dicatat' }}
                            </div>
                        </div>

                        <div class="rounded-ta border-[1.5px] border-ink bg-mint/45 p-4">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-muted">
                                <x-ikon e="😊" class="size-4" /> Suasana Hati
                            </div>
                            <div class="mt-1.5 text-base font-bold">{{ $laporan->labelSuasanaHati() }}</div>
                        </div>

                        <div class="rounded-ta border-[1.5px] border-ink bg-cream p-4">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-muted">
                                <x-ikon e="🧻" class="size-4" /> BAB / BAK
                            </div>
                            <div class="mt-1.5 text-sm font-semibold">
                                {{ $laporan->catatan_buang_air ?: 'Tidak ada catatan khusus' }}
                            </div>
                        </div>
                    </div>

                    {{-- Aktivitas & Catatan Umum --}}
                    <div class="grid gap-3">
                        <div class="rounded-ta border-[1.5px] border-ink bg-cream/60 p-4">
                            <h3 class="text-sm font-bold">Aktivitas Hari Ini</h3>
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed">{{ $laporan->catatan_aktivitas }}</p>
                        </div>

                        @if ($laporan->catatan_umum)
                            <div class="rounded-ta border-[1.5px] border-ink bg-lavender/35 p-4">
                                <h3 class="text-sm font-bold">Pesan Pengasuh untuk Orang Tua</h3>
                                <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed">{{ $laporan->catatan_umum }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Galeri Foto Kegiatan (User Story 2) --}}
                    <div class="grid gap-3 border-t border-ink/20 pt-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold">Foto Kegiatan ({{ $laporan->lampiran->count() }})</h3>
                            @if ($laporan->lampiran->isNotEmpty())
                                <span class="text-xs text-muted">Klik foto untuk memperbesar</span>
                            @endif
                        </div>

                        @if ($laporan->lampiran->isEmpty())
                            <div class="rounded-ta border border-dashed border-ink/40 bg-cream/40 p-5 text-center text-xs text-muted">
                                Belum ada foto kegiatan yang dilampirkan pada laporan ini.
                            </div>
                        @else
                            <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
                                @foreach ($laporan->lampiran as $foto)
                                    <figure wire:key="ortu-foto-{{ $foto->id }}"
                                        class="group overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                                        <button type="button" wire:click="bukaFoto({{ $foto->id }})"
                                            class="block w-full overflow-hidden text-left focus-visible:outline-3 focus-visible:outline-yellow">
                                            <img src="{{ $foto->url() }}"
                                                alt="{{ $foto->keterangan ?: 'Foto kegiatan '.$anakTerpilih->nama_lengkap }}"
                                                loading="lazy"
                                                class="h-48 w-full object-cover transition duration-200 group-hover:scale-[1.02]">
                                        </button>
                                        <figcaption class="border-t-[1.5px] border-ink bg-cream px-3.5 py-2.5 text-xs font-medium">
                                            {{ $foto->keterangan ?: 'Dokumentasi kegiatan hari ini' }}
                                        </figcaption>
                                    </figure>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    @endif

    {{-- Modal Lightbox Perbesar Foto --}}
    @if ($fotoPreview)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/75 p-4"
            role="dialog" aria-modal="true" aria-label="Pratinjau foto kegiatan"
            wire:click.self="tutupFoto">
            <div class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                <div class="flex items-center justify-between gap-3 border-b-[1.5px] border-ink bg-yellow px-5 py-3.5">
                    <span class="truncate text-sm font-bold">
                        {{ $fotoPreview->keterangan ?: 'Foto Kegiatan '.$anakTerpilih?->nama_lengkap }}
                    </span>
                    <button type="button" wire:click="tutupFoto" aria-label="Tutup pratinjau"
                        class="grid size-8 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-xs font-bold hover:bg-cream">
                        ✕
                    </button>
                </div>
                <div class="flex items-center justify-center overflow-auto bg-cream p-3">
                    <img src="{{ $fotoPreview->url() }}"
                        alt="{{ $fotoPreview->keterangan ?: 'Foto kegiatan' }}"
                        class="max-h-[72vh] w-auto rounded-lg border border-ink object-contain">
                </div>
            </div>
        </div>
    @endif
</div>