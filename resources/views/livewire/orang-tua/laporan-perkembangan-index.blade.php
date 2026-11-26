<div class="grid gap-6">
    {{-- Tajuk Halaman --}}
    <div class="flex flex-wrap items-start justify-between gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Laporan Perkembangan Anak</h1>
            <p class="mt-1 text-sm text-muted">
                Tinjau dan unduh evaluasi tumbuh kembang bulanan buah hati Anda dari pengasuh.
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-ink bg-lavender px-3 py-1.5 text-xs font-semibold">
            <x-ikon e="📈" class="size-4" /> Orang Tua
        </span>
    </div>

    @if ($daftarAnak->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
            <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-cream text-2xl">
                <x-ikon e="🧒" class="size-7" />
            </div>
            <h2 class="text-lg font-bold">Belum ada data anak aktif</h2>
            <p class="mt-1 text-sm text-muted">
                Laporan perkembangan bulanan akan tampil setelah pendaftaran anak Anda diverifikasi dan berstatus aktif.
            </p>
        </div>
    @else
        {{-- Pemilih Anak & Periode Bulan --}}
        <div class="grid gap-4 rounded-ta border-[1.5px] border-ink bg-cream p-4 print:hidden">
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
                    <label for="filter-periode-ortu" class="text-sm font-semibold">Periode Bulan:</label>
                    <input id="filter-periode-ortu" type="month" wire:model.live="periode"
                        class="rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm font-medium focus-visible:outline-3 focus-visible:outline-yellow">
                </div>

                @if ($riwayatLaporan->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-semibold text-muted">Riwayat penilaian:</span>
                        @foreach ($riwayatLaporan as $riwayat)
                            <button type="button" wire:click="pilihPeriode('{{ $riwayat->periode_bulan->format('Y-m') }}')"
                                class="rounded-full border border-ink px-3 py-1 text-xs font-semibold transition
                                    {{ $periodeObjek->isSameMonth($riwayat->periode_bulan) ? 'bg-mint border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                                {{ $riwayat->periode_bulan->translatedFormat('M Y') }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if ($anakTerpilih)
            {{-- Kartu Identitas Anak & Tombol Unduh / Cetak --}}
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
                            Periode Penilaian: <strong class="text-ink">{{ $periodeObjek->translatedFormat('F Y') }}</strong>
                            @if ($laporanTerpilih)
                                · Dinilai oleh <strong class="text-ink">{{ $laporanTerpilih->pengasuh?->nama ?? 'Pengasuh' }}</strong>
                            @endif
                        </p>
                    </div>
                </div>

                @if ($laporanTerpilih)
                    <div class="flex flex-wrap items-center gap-2 print:hidden">
                        <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5 hover:bg-cream">
                            <x-ikon e="🖨️" class="size-4" /> Cetak / PDF
                        </button>
                        <a href="{{ route('orang-tua.laporan-perkembangan.unduh', $laporanTerpilih) }}"
                            class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-xs font-semibold transition hover:-translate-y-0.5">
                            <x-ikon e="⬇️" class="size-4" /> Unduh Rapor
                        </a>
                    </div>
                @endif
            </div>

            {{-- Isi Penilaian Bulanan --}}
            @if ($laporanTerpilih === null)
                <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
                    <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-yellow text-2xl">
                        <x-ikon e="📋" class="size-7" />
                    </div>
                    <h3 class="text-lg font-bold">Penilaian periode {{ $periodeObjek->translatedFormat('F Y') }} belum tersedia</h3>
                    <p class="mx-auto mt-1 max-w-md text-sm text-muted">
                        Pengasuh belum menerbitkan laporan perkembangan untuk bulan ini. Silakan pilih periode lain pada riwayat penilaian di atas.
                    </p>
                </div>
            @else
                <div class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-ink/20 pb-3">
                        <div class="text-xs text-muted">
                            Pengasuh Penilai: <strong class="text-ink">{{ $laporanTerpilih->pengasuh?->nama ?? 'Pengasuh Daycare' }}</strong>
                        </div>
                        <div class="text-xs text-muted">
                            Terakhir diperbarui: {{ $laporanTerpilih->updated_at?->translatedFormat('d M Y, H:i') ?? '—' }}
                        </div>
                    </div>

                    {{-- 4 Aspek Tumbuh Kembang --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <section class="rounded-ta border-[1.5px] border-ink bg-yellow/35 p-4">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                                <x-ikon e="🏃" class="size-4" /> 1. Perkembangan Motorik
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $laporanTerpilih->catatan_motorik ?: '—' }}</p>
                        </section>

                        <section class="rounded-ta border-[1.5px] border-ink bg-blue/35 p-4">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                                <x-ikon e="🗣️" class="size-4" /> 2. Perkembangan Bahasa
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $laporanTerpilih->catatan_bahasa ?: '—' }}</p>
                        </section>

                        <section class="rounded-ta border-[1.5px] border-ink bg-mint/45 p-4">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                                <x-ikon e="🤝" class="size-4" /> 3. Sosial &amp; Emosional
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $laporanTerpilih->catatan_sosial ?: '—' }}</p>
                        </section>

                        <section class="rounded-ta border-[1.5px] border-ink bg-lavender/45 p-4">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                                <x-ikon e="🧩" class="size-4" /> 4. Perkembangan Kognitif
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $laporanTerpilih->catatan_kognitif ?: '—' }}</p>
                        </section>
                    </div>

                    {{-- Catatan Umum --}}
                    <section class="rounded-ta border-[1.5px] border-ink bg-cream p-4">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                            <x-ikon e="💌" class="size-4" /> 5. Catatan Umum &amp; Saran Stimulasi
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">
                            {{ $laporanTerpilih->catatan_umum ?: 'Tidak ada catatan tambahan untuk periode ini.' }}
                        </p>
                    </section>
                </div>
            @endif
        @endif
    @endif
</div>