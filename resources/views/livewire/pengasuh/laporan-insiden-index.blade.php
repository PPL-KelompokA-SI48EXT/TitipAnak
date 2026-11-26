@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-sm focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $galat = 'text-xs font-semibold text-[#B02E12]';
@endphp

<div class="grid gap-6">
    {{-- Tajuk Halaman --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Laporan Insiden</h1>
            <p class="mt-1 text-sm text-muted">
                Catat kejadian khusus, cedera ringan, atau kondisi kesehatan mendadak anak serta perbarui tindakan penanganan.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-ink bg-blue px-3 py-1.5 text-xs font-semibold">
                <x-ikon e="🧸" class="size-4" /> Pengasuh
            </span>
            <button type="button" wire:click="mulaiBuat"
                class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-coral px-4 py-2 text-xs font-semibold transition hover:-translate-y-0.5">
                <x-ikon e="🚑" class="size-4" /> Catat Insiden Baru
            </button>
        </div>
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

    {{-- Toolbar Filter Tanggal & Pencarian --}}
    <div class="flex flex-wrap items-center gap-3 rounded-ta border-[1.5px] border-ink bg-cream p-4">
        <div class="relative min-w-[220px] flex-1">
            <input type="search" wire:model.live.debounce.300ms="q"
                placeholder="Cari nama anak atau judul insiden…"
                class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm focus-visible:outline-3 focus-visible:outline-yellow">
        </div>
        <div>
            <input type="date" wire:model.live="tanggal" aria-label="Pilih tanggal insiden"
                class="rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm font-medium focus-visible:outline-3 focus-visible:outline-yellow">
        </div>
    </div>

    {{-- Daftar Laporan Insiden --}}
    @if ($daftarInsiden->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
            <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-mint text-2xl">
                <x-ikon e="🛡️" class="size-7" />
            </div>
            <h2 class="text-lg font-bold">Tidak ada laporan insiden</h2>
            <p class="mt-1 text-sm text-muted">
                Belum ada insiden yang dicatat pada {{ $tanggalObjek->translatedFormat('l, d M Y') }}.
            </p>
            @if ($q !== '' || $tanggal !== today()->toDateString())
                <button type="button" wire:click="resetFilter"
                    class="mt-4 inline-flex items-center rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-xs font-semibold">
                    Reset Filter
                </button>
            @endif
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($daftarInsiden as $insiden)
                <article wire:key="insiden-{{ $insiden->id }}" class="rounded-ta border-[1.5px] border-ink bg-paper p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-coral text-base font-bold">
                                {{ mb_strtoupper(mb_substr($insiden->anak?->nama_lengkap ?? 'A', 0, 1)) }}
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-bold">{{ $insiden->judul }}</h3>
                                    <span class="rounded-full border border-ink bg-coral px-2.5 py-0.5 text-xs font-semibold">
                                        Pukul {{ $insiden->waktu_kejadian->format('H:i') }}
                                    </span>
                                    <span class="rounded-full border border-ink bg-cream px-2.5 py-0.5 text-xs font-semibold">
                                        {{ $insiden->anak?->nama_lengkap ?? '—' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted">
                                    Kelompok {{ $insiden->anak?->kelompokUsia?->nama ?? '—' }}
                                    · Dicatat oleh {{ $insiden->pengasuh?->nama ?? 'Pengasuh' }}
                                    @if ($insiden->updated_at && $insiden->updated_at->ne($insiden->created_at))
                                        · Diperbarui {{ $insiden->updated_at->format('H:i') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <button type="button" wire:click="mulaiUbah({{ $insiden->id }})"
                            class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5 hover:bg-cream">
                            <x-ikon e="✏️" class="size-4" /> Ubah / Tambah Info
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

    {{-- Modal Form Buat / Ubah Laporan Insiden --}}
    @if ($jam_kejadian !== '' || $modeUbah)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/55 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-insiden-judul">
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                <div class="flex items-center justify-between gap-3 border-b-[1.5px] border-ink bg-coral px-5 py-4">
                    <div>
                        <h2 id="modal-insiden-judul" class="text-lg font-bold">
                            {{ $modeUbah ? 'Ubah Laporan Insiden' : 'Catat Laporan Insiden Baru' }}
                        </h2>
                        <p class="text-xs font-medium">
                            Tanggal: {{ $tanggalObjek->translatedFormat('l, d M Y') }}
                        </p>
                    </div>
                    <button type="button" wire:click="batalForm" aria-label="Tutup"
                        class="grid size-9 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-sm font-bold hover:bg-cream">
                        ✕
                    </button>
                </div>

                <form wire:submit="simpan" class="grid gap-4 overflow-y-auto p-5">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-1.5 sm:col-span-2">
                            <label for="anak_id" class="text-sm font-semibold">Anak yang Terlibat <span class="text-[#B02E12]">*</span></label>
                            <select id="anak_id" wire:model="anak_id" class="{{ $input }}" @disabled($modeUbah)>
                                <option value="">— Pilih anak —</option>
                                @foreach ($anakDiasuh as $a)
                                    <option value="{{ $a->id }}">{{ $a->nama_lengkap }} ({{ $a->kelompokUsia?->nama ?? '—' }})</option>
                                @endforeach
                            </select>
                            @error('anak_id') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid gap-1.5">
                            <label for="jam_kejadian" class="text-sm font-semibold">Jam Kejadian <span class="text-[#B02E12]">*</span></label>
                            <input id="jam_kejadian" type="time" wire:model="jam_kejadian" class="{{ $input }}">
                            @error('jam_kejadian') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="judul" class="text-sm font-semibold">Judul / Jenis Insiden <span class="text-[#B02E12]">*</span></label>
                        <input id="judul" type="text" wire:model="judul"
                            placeholder="Contoh: Terjatuh ringan saat bermain perosotan"
                            class="{{ $input }}">
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            @foreach ($judulCepatList as $pilihanJudul)
                                <button type="button" wire:click="pakaiJudul('{{ $pilihanJudul }}')"
                                    class="rounded-full border border-ink bg-cream px-2.5 py-0.5 text-xs font-semibold hover:bg-yellow">
                                    {{ $pilihanJudul }}
                                </button>
                            @endforeach
                        </div>
                        @error('judul') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="deskripsi" class="text-sm font-semibold">Kronologi &amp; Kondisi Anak <span class="text-[#B02E12]">*</span></label>
                        <textarea id="deskripsi" rows="3" wire:model="deskripsi"
                            placeholder="Jelaskan bagaimana kejadian berlangsung dan bagian tubuh / kondisi anak saat itu…"
                            class="{{ $input }}"></textarea>
                        @error('deskripsi') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="tindakan" class="text-sm font-semibold">Tindakan Penanganan &amp; Informasi Tambahan</label>
                        <textarea id="tindakan" rows="3" wire:model="tindakan"
                            placeholder="Contoh: Luka dibersihkan dengan antiseptik, dikompres dingin, anak kembali tenang setelah 10 menit…"
                            class="{{ $input }}"></textarea>
                        @error('tindakan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t-[1.5px] border-ink bg-cream -mx-5 -mb-5 mt-2 px-5 py-4">
                        <button type="button" wire:click="batalForm"
                            class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold hover:bg-cream">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                            <span wire:loading.remove wire:target="simpan">{{ $modeUbah ? 'Simpan Perubahan' : 'Simpan Laporan Insiden' }}</span>
                            <span wire:loading wire:target="simpan">Menyimpan…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>