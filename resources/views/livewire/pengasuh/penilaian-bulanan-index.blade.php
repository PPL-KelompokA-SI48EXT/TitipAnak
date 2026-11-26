@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-sm focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $galat = 'text-xs font-semibold text-[#B02E12]';
@endphp

<div class="grid gap-6">
    {{-- Tajuk Halaman --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Penilaian Bulanan</h1>
            <p class="mt-1 text-sm text-muted">
                Evaluasi perkembangan motorik, bahasa, sosial-emosional, dan kognitif anak setiap bulan.
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-ink bg-blue px-3 py-1.5 text-xs font-semibold">
            <x-ikon e="🧸" class="size-4" /> Pengasuh
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

    {{-- Kartu Ringkasan Periode --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-ta border-[1.5px] border-ink bg-blue p-5">
            <div class="text-3xl font-bold leading-none">{{ $totalAnak }}</div>
            <div class="mt-2 text-sm font-bold">Total Anak Asuh</div>
            <div class="text-xs opacity-80">Periode {{ $periodeObjek->translatedFormat('F Y') }}</div>
        </div>
        <div class="rounded-ta border-[1.5px] border-ink bg-mint p-5">
            <div class="text-3xl font-bold leading-none">{{ $jumlahSudah }}</div>
            <div class="mt-2 text-sm font-bold">Sudah Dinilai</div>
            <div class="text-xs opacity-80">Laporan perkembangan terisi</div>
        </div>
        <div class="rounded-ta border-[1.5px] border-ink bg-yellow p-5">
            <div class="text-3xl font-bold leading-none">{{ $jumlahBelum }}</div>
            <div class="mt-2 text-sm font-bold">Belum Dinilai</div>
            <div class="text-xs opacity-80">Menunggu penilaian bulan ini</div>
        </div>
    </div>

    {{-- Toolbar Filter Periode, Pencarian & Status --}}
    <div class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-cream p-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[220px] flex-1">
                <input type="search" wire:model.live.debounce.300ms="q"
                    placeholder="Cari nama anak…"
                    class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm focus-visible:outline-3 focus-visible:outline-yellow">
            </div>
            <div class="flex items-center gap-2">
                <label for="periode-bulan" class="text-xs font-semibold text-muted">Periode:</label>
                <input id="periode-bulan" type="month" wire:model.live="periode"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm font-medium focus-visible:outline-3 focus-visible:outline-yellow">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="pilihStatus('')"
                class="rounded-full border border-ink px-3.5 py-1.5 text-xs font-semibold transition {{ $status === '' ? 'bg-yellow border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                Semua ({{ $totalAnak }})
            </button>
            <button type="button" wire:click="pilihStatus('belum')"
                class="rounded-full border border-ink px-3.5 py-1.5 text-xs font-semibold transition {{ $status === 'belum' ? 'bg-yellow border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                Belum Dinilai ({{ $jumlahBelum }})
            </button>
            <button type="button" wire:click="pilihStatus('sudah')"
                class="rounded-full border border-ink px-3.5 py-1.5 text-xs font-semibold transition {{ $status === 'sudah' ? 'bg-yellow border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                Sudah Dinilai ({{ $jumlahSudah }})
            </button>
        </div>
    </div>

    {{-- Daftar Anak --}}
    @if ($daftarAnak->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
            <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-cream text-2xl">
                <x-ikon e="📈" class="size-7" />
            </div>
            <h2 class="text-lg font-bold">Belum ada data anak</h2>
            <p class="mt-1 text-sm text-muted">
                Tidak ada anak aktif di kelompok tanggung jawab Anda, atau filter pencarian tidak cocok.
            </p>
            @if ($q !== '' || $status !== '' || $periode !== today()->format('Y-m'))
                <button type="button" wire:click="resetFilter"
                    class="mt-4 inline-flex items-center rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-xs font-semibold">
                    Reset Filter
                </button>
            @endif
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($daftarAnak as $anak)
                @php
                    /** @var \App\Models\LaporanPerkembangan|null $penilaian */
                    $penilaian = $anak->penilaian_bulan_ini;
                @endphp
                <div wire:key="anak-nilai-{{ $anak->id }}" class="rounded-ta border-[1.5px] border-ink bg-paper p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-blue text-base font-bold">
                                {{ mb_strtoupper(mb_substr($anak->nama_lengkap, 0, 1)) }}
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-bold">{{ $anak->nama_lengkap }}</h3>
                                    <span class="rounded-full border border-ink bg-cream px-2.5 py-0.5 text-xs font-semibold">
                                        {{ $anak->kelompokUsia?->nama ?? '—' }}
                                    </span>
                                    @if ($penilaian)
                                        <span class="rounded-full border border-ink bg-mint px-2.5 py-0.5 text-xs font-semibold">
                                            Sudah Dinilai
                                        </span>
                                    @else
                                        <span class="rounded-full border border-ink bg-yellow px-2.5 py-0.5 text-xs font-semibold">
                                            Belum Dinilai
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-muted">
                                    Periode: <strong>{{ $periodeObjek->translatedFormat('F Y') }}</strong>
                                    @if ($penilaian)
                                        · Dinilai oleh {{ $penilaian->pengasuh?->nama ?? 'Pengasuh' }}
                                        ({{ $penilaian->updated_at?->translatedFormat('d M Y') }})
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($penilaian)
                                <button type="button" wire:click="bukaForm({{ $anak->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5 hover:bg-cream">
                                    <x-ikon e="✏️" class="size-4" /> Ubah Penilaian
                                </button>
                            @else
                                <button type="button" wire:click="bukaForm({{ $anak->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-xs font-semibold transition hover:-translate-y-0.5">
                                    <x-ikon e="📈" class="size-4" /> Buat Penilaian
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Ringkasan 4 Aspek Perkembangan (User Story 2) --}}
                    @if ($penilaian)
                        <div class="mt-4 grid gap-3 border-t border-ink/20 pt-4 text-sm sm:grid-cols-2">
                            <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                                <div class="text-xs font-bold text-muted">1. Perkembangan Motorik</div>
                                <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_motorik }}</p>
                            </div>
                            <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                                <div class="text-xs font-bold text-muted">2. Perkembangan Bahasa</div>
                                <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_bahasa }}</p>
                            </div>
                            <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                                <div class="text-xs font-bold text-muted">3. Sosial &amp; Emosional</div>
                                <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_sosial }}</p>
                            </div>
                            <div class="rounded-lg border border-ink/30 bg-cream/60 p-3.5">
                                <div class="text-xs font-bold text-muted">4. Perkembangan Kognitif</div>
                                <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_kognitif }}</p>
                            </div>

                            @if ($penilaian->catatan_umum)
                                <div class="rounded-lg border border-ink/30 bg-mint/30 p-3.5 sm:col-span-2">
                                    <div class="text-xs font-bold text-muted">Catatan Umum &amp; Rekomendasi</div>
                                    <p class="mt-1 whitespace-pre-line leading-relaxed">{{ $penilaian->catatan_umum }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal Form Buat / Ubah Penilaian Bulanan --}}
    @if ($terpilihAnak)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/55 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-penilaian-judul">
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                <div class="flex items-center justify-between gap-3 border-b-[1.5px] border-ink bg-yellow px-5 py-4">
                    <div>
                        <h2 id="modal-penilaian-judul" class="text-lg font-bold">
                            {{ $penilaianId ? 'Ubah Penilaian Bulanan' : 'Buat Penilaian Bulanan' }}
                        </h2>
                        <p class="text-xs font-medium">
                            {{ $terpilihAnak->nama_lengkap }} · Periode {{ $periodeObjek->translatedFormat('F Y') }}
                        </p>
                    </div>
                    <button type="button" wire:click="batalForm" aria-label="Tutup"
                        class="grid size-9 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-sm font-bold hover:bg-cream">
                        ✕
                    </button>
                </div>

                <form wire:submit="simpanPenilaian" class="grid gap-4 overflow-y-auto p-5">
                    <div class="grid gap-1.5">
                        <label for="catatan_motorik" class="text-sm font-semibold">
                            1. Perkembangan Motorik (Halus &amp; Kasar) <span class="text-[#B02E12]">*</span>
                        </label>
                        <textarea id="catatan_motorik" rows="2" wire:model="catatan_motorik"
                            placeholder="Contoh: Mampu melompat dengan seimbang, memegang krayon dengan baik…"
                            class="{{ $input }}"></textarea>
                        @error('catatan_motorik') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="catatan_bahasa" class="text-sm font-semibold">
                            2. Perkembangan Bahasa &amp; Komunikasi <span class="text-[#B02E12]">*</span>
                        </label>
                        <textarea id="catatan_bahasa" rows="2" wire:model="catatan_bahasa"
                            placeholder="Contoh: Kosakata bertambah, mampu menjawab pertanyaan sederhana…"
                            class="{{ $input }}"></textarea>
                        @error('catatan_bahasa') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="catatan_sosial" class="text-sm font-semibold">
                            3. Perkembangan Sosial &amp; Emosional <span class="text-[#B02E12]">*</span>
                        </label>
                        <textarea id="catatan_sosial" rows="2" wire:model="catatan_sosial"
                            placeholder="Contoh: Mau berbagi mainan dengan teman, mampu menenangkan diri…"
                            class="{{ $input }}"></textarea>
                        @error('catatan_sosial') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="catatan_kognitif" class="text-sm font-semibold">
                            4. Perkembangan Kognitif <span class="text-[#B02E12]">*</span>
                        </label>
                        <textarea id="catatan_kognitif" rows="2" wire:model="catatan_kognitif"
                            placeholder="Contoh: Mengenal warna dasar, mampu menyusun balok sesuai ukuran…"
                            class="{{ $input }}"></textarea>
                        @error('catatan_kognitif') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="catatan_umum" class="text-sm font-semibold">
                            5. Catatan Umum &amp; Saran Stimulasi di Rumah
                        </label>
                        <textarea id="catatan_umum" rows="2" wire:model="catatan_umum"
                            placeholder="Opsional: rangkuman perkembangan atau saran kegiatan bersama orang tua…"
                            class="{{ $input }}"></textarea>
                        @error('catatan_umum') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t-[1.5px] border-ink bg-cream -mx-5 -mb-5 mt-2 px-5 py-4">
                        <button type="button" wire:click="batalForm"
                            class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold hover:bg-cream">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                            <span wire:loading.remove wire:target="simpanPenilaian">Simpan Penilaian</span>
                            <span wire:loading wire:target="simpanPenilaian">Menyimpan…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>