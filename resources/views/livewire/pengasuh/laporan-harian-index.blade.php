@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-sm focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $galat = 'text-xs font-semibold text-[#B02E12]';
@endphp

<div class="grid gap-6">
    {{-- Tajuk Halaman --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Laporan Harian</h1>
            <p class="mt-1 text-sm text-muted">
                Catat makan, tidur, aktivitas, dan foto kegiatan anak yang sudah check-in.
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

    {{-- Kartu Ringkasan --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-ta border-[1.5px] border-ink bg-blue p-5">
            <div class="text-3xl font-bold leading-none">{{ $totalAnak }}</div>
            <div class="mt-2 text-sm font-bold">Anak Check-in</div>
            <div class="text-xs opacity-80">{{ $tanggalObjek->translatedFormat('l, d M Y') }}</div>
        </div>
        <div class="rounded-ta border-[1.5px] border-ink bg-mint p-5">
            <div class="text-3xl font-bold leading-none">{{ $jumlahSudah }}</div>
            <div class="mt-2 text-sm font-bold">Sudah Dilaporkan</div>
            <div class="text-xs opacity-80">Laporan harian terisi</div>
        </div>
        <div class="rounded-ta border-[1.5px] border-ink bg-yellow p-5">
            <div class="text-3xl font-bold leading-none">{{ $jumlahBelum }}</div>
            <div class="mt-2 text-sm font-bold">Belum Dilaporkan</div>
            <div class="text-xs opacity-80">Menunggu catatan pengasuh</div>
        </div>
    </div>

    {{-- Toolbar Filter --}}
    <div class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-cream p-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[220px] flex-1">
                <input type="search" wire:model.live.debounce.300ms="q"
                    placeholder="Cari nama anak…"
                    class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-sm focus-visible:outline-3 focus-visible:outline-yellow">
            </div>
            <div>
                <input type="date" wire:model.live="tanggal" aria-label="Pilih tanggal laporan"
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
                Belum Dilaporkan ({{ $jumlahBelum }})
            </button>
            <button type="button" wire:click="pilihStatus('sudah')"
                class="rounded-full border border-ink px-3.5 py-1.5 text-xs font-semibold transition {{ $status === 'sudah' ? 'bg-yellow border-[1.5px]' : 'bg-paper hover:bg-cream' }}">
                Sudah Dilaporkan ({{ $jumlahSudah }})
            </button>
        </div>
    </div>

    {{-- Daftar Anak --}}
    @if ($daftarAnak->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-paper p-8 text-center">
            <div class="mx-auto mb-3 grid size-14 place-items-center rounded-full border-[1.5px] border-ink bg-cream text-2xl">
                <x-ikon e="📒" class="size-7" />
            </div>
            <h2 class="text-lg font-bold">Belum ada data anak</h2>
            <p class="mt-1 text-sm text-muted">
                Tidak ada anak yang check-in di kelompok Anda pada tanggal ini, atau filter tidak cocok.
            </p>
            @if ($q !== '' || $status !== '' || $tanggal !== today()->toDateString())
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
                    /** @var \App\Models\LaporanHarian|null $laporan */
                    $laporan = $anak->laporan_hari_ini;
                    $presensi = $anak->kehadiran->first();
                @endphp
                <div wire:key="anak-{{ $anak->id }}" class="rounded-ta border-[1.5px] border-ink bg-paper p-5">
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
                                    @if ($laporan)
                                        <span class="rounded-full border border-ink bg-mint px-2.5 py-0.5 text-xs font-semibold">
                                            Sudah Dilaporkan
                                        </span>
                                    @else
                                        <span class="rounded-full border border-ink bg-yellow px-2.5 py-0.5 text-xs font-semibold">
                                            Belum Dilaporkan
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-muted">
                                    Check-in pukul {{ $presensi?->check_in_pada?->format('H:i') ?? '—' }}
                                    @if ($presensi?->kondisi_check_in)
                                        · Kondisi datang: {{ $presensi->kondisi_check_in }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($laporan)
                                <button type="button" wire:click="bukaForm({{ $anak->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5 hover:bg-cream">
                                    <x-ikon e="✏️" class="size-4" /> Ubah &amp; Kelola Foto
                                </button>
                                <button type="button" wire:click="konfirmasiHapus({{ $laporan->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-coral px-3.5 py-2 text-xs font-semibold transition hover:-translate-y-0.5">
                                    <x-ikon e="🗑️" class="size-4" /> Hapus
                                </button>
                            @else
                                <button type="button" wire:click="bukaForm({{ $anak->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-xs font-semibold transition hover:-translate-y-0.5">
                                    <x-ikon e="📝" class="size-4" /> Buat Laporan
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($laporan)
                        <div class="mt-4 grid gap-3 border-t border-ink/20 pt-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-lg border border-ink/30 bg-cream/60 p-3">
                                <div class="text-xs font-semibold text-muted">Makan</div>
                                <div class="mt-0.5 font-bold">{{ $laporan->labelStatusMakan() }}</div>
                                @if ($laporan->catatan_makan)
                                    <div class="mt-1 text-xs text-muted">{{ $laporan->catatan_makan }}</div>
                                @endif
                            </div>
                            <div class="rounded-lg border border-ink/30 bg-cream/60 p-3">
                                <div class="text-xs font-semibold text-muted">Tidur Siang</div>
                                <div class="mt-0.5 font-bold">{{ $laporan->durasi_tidur_menit !== null ? $laporan->durasi_tidur_menit.' menit' : '—' }}</div>
                            </div>
                            <div class="rounded-lg border border-ink/30 bg-cream/60 p-3">
                                <div class="text-xs font-semibold text-muted">Suasana Hati</div>
                                <div class="mt-0.5 font-bold">{{ $laporan->labelSuasanaHati() }}</div>
                            </div>
                            <div class="rounded-lg border border-ink/30 bg-cream/60 p-3">
                                <div class="text-xs font-semibold text-muted">BAB / BAK</div>
                                <div class="mt-0.5 font-medium">{{ $laporan->catatan_buang_air ?: '—' }}</div>
                            </div>
                            @if ($laporan->catatan_aktivitas || $laporan->catatan_umum)
                                <div class="rounded-lg border border-ink/30 bg-paper p-3 sm:col-span-2 lg:col-span-4">
                                    @if ($laporan->catatan_aktivitas)
                                        <div><span class="font-semibold">Aktivitas:</span> {{ $laporan->catatan_aktivitas }}</div>
                                    @endif
                                    @if ($laporan->catatan_umum)
                                        <div class="mt-1"><span class="font-semibold">Catatan:</span> {{ $laporan->catatan_umum }}</div>
                                    @endif
                                </div>
                            @endif

                            {{-- Galeri Foto Kegiatan --}}
                            @if ($laporan->lampiran->isNotEmpty())
                                <div class="sm:col-span-2 lg:col-span-4">
                                    <div class="mb-2 text-xs font-semibold text-muted">Foto Kegiatan ({{ $laporan->lampiran->count() }})</div>
                                    <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                                        @foreach ($laporan->lampiran as $foto)
                                            <figure wire:key="kartu-foto-{{ $foto->id }}" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                                                <img src="{{ $foto->url() }}" alt="{{ $foto->keterangan ?: 'Foto kegiatan '.$anak->nama_lengkap }}"
                                                    loading="lazy" class="h-40 w-full object-cover">
                                                <figcaption class="border-t border-ink bg-cream px-3 py-2 text-xs">
                                                    {{ $foto->keterangan ?: 'Dokumentasi kegiatan' }}
                                                </figcaption>
                                            </figure>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal Form Buat / Ubah Laporan & Foto Kegiatan --}}
    @if ($terpilihAnak)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/55 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-laporan-judul">
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                <div class="flex items-center justify-between gap-3 border-b-[1.5px] border-ink bg-yellow px-5 py-4">
                    <div>
                        <h2 id="modal-laporan-judul" class="text-lg font-bold">
                            {{ $laporanId ? 'Ubah Laporan & Kelola Foto' : 'Buat Laporan Harian' }}
                        </h2>
                        <p class="text-xs font-medium">
                            {{ $terpilihAnak->nama_lengkap }} · {{ $tanggalObjek->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <button type="button" wire:click="batalForm" aria-label="Tutup"
                        class="grid size-9 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-sm font-bold hover:bg-cream">
                        ✕
                    </button>
                </div>

                <form wire:submit="simpanLaporan" class="grid gap-4 overflow-y-auto p-5">
                    {{-- Status Makan & Suasana Hati --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <label for="status_makan" class="text-sm font-semibold">Status Makan</label>
                            <select id="status_makan" wire:model="status_makan" class="{{ $input }}">
                                @foreach ($opsiMakan as $nilai => $label)
                                    <option value="{{ $nilai }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status_makan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid gap-1.5">
                            <label for="suasana_hati" class="text-sm font-semibold">Suasana Hati / Kondisi</label>
                            <select id="suasana_hati" wire:model="suasana_hati" class="{{ $input }}">
                                @foreach ($opsiSuasanaHati as $nilai => $label)
                                    <option value="{{ $nilai }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('suasana_hati') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Catatan Makan --}}
                    <div class="grid gap-1.5">
                        <label for="catatan_makan" class="text-sm font-semibold">Catatan Makan</label>
                        <input id="catatan_makan" type="text" wire:model="catatan_makan"
                            placeholder="Contoh: Makan siang habis 1 porsi, minum susu 120ml"
                            class="{{ $input }}">
                        @error('catatan_makan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    {{-- Durasi Tidur Siang & BAB/BAK --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <label for="durasi_tidur_menit" class="text-sm font-semibold">Durasi Tidur Siang (menit)</label>
                            <input id="durasi_tidur_menit" type="number" min="0" max="600" wire:model="durasi_tidur_menit"
                                placeholder="Contoh: 90"
                                class="{{ $input }}">
                            <div class="flex flex-wrap gap-1.5 pt-0.5">
                                @foreach ([0 => 'Tidak tidur', 45 => '45 mnt', 60 => '60 mnt', 90 => '90 mnt', 120 => '120 mnt'] as $menit => $teks)
                                    <button type="button" wire:click="pakaiDurasiTidur({{ $menit }})"
                                        class="rounded-full border border-ink bg-cream px-2.5 py-0.5 text-xs font-semibold hover:bg-yellow">
                                        {{ $teks }}
                                    </button>
                                @endforeach
                            </div>
                            @error('durasi_tidur_menit') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid gap-1.5">
                            <label for="catatan_buang_air" class="text-sm font-semibold">Catatan BAB / BAK</label>
                            <input id="catatan_buang_air" type="text" wire:model="catatan_buang_air"
                                placeholder="Contoh: BAK 3x, BAB 1x normal"
                                class="{{ $input }}">
                            @error('catatan_buang_air') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Catatan Aktivitas --}}
                    <div class="grid gap-1.5">
                        <label for="catatan_aktivitas" class="text-sm font-semibold">Aktivitas Hari Ini <span class="text-[#B02E12]">*</span></label>
                        <textarea id="catatan_aktivitas" rows="3" wire:model="catatan_aktivitas"
                            placeholder="Ceritakan kegiatan bermain, belajar, atau interaksi anak hari ini…"
                            class="{{ $input }}"></textarea>
                        @error('catatan_aktivitas') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    {{-- Catatan Umum --}}
                    <div class="grid gap-1.5">
                        <label for="catatan_umum" class="text-sm font-semibold">Catatan Tambahan untuk Orang Tua</label>
                        <textarea id="catatan_umum" rows="2" wire:model="catatan_umum"
                            placeholder="Opsional: pesan khusus, perlengkapan yang habis, dll."
                            class="{{ $input }}"></textarea>
                        @error('catatan_umum') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>

                    {{-- Bagian Foto Kegiatan --}}
                    <div class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-cream p-4">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-bold">Foto Kegiatan (Bukti Visual)</span>
                            <span class="text-xs text-muted">JPG / PNG / WebP · Maks. 2 MB per foto</span>
                        </div>

                        {{-- Daftar Foto yang Sudah Terunggah (Ubah / Hapus) --}}
                        @if ($lampiranTerpilih->isNotEmpty())
                            <div class="grid gap-2.5">
                                @foreach ($lampiranTerpilih as $lampiran)
                                    <div wire:key="edit-foto-{{ $lampiran->id }}" class="rounded-ta border border-ink bg-paper p-3">
                                        @if ($gantiLampiranId === $lampiran->id)
                                            <div class="grid gap-2.5">
                                                <div class="text-xs font-bold">Ubah Foto / Keterangan</div>
                                                <input type="file" wire:model="filePengganti" accept="image/jpeg,image/png,image/webp"
                                                    class="block w-full text-xs file:mr-3 file:rounded-ta file:border file:border-ink file:bg-yellow file:px-3 file:py-1.5 file:font-semibold">
                                                @error('filePengganti') <span class="{{ $galat }}">{{ $message }}</span> @enderror

                                                <input type="text" wire:model="keteranganPengganti" placeholder="Keterangan foto…" class="{{ $input }}">
                                                @error('keteranganPengganti') <span class="{{ $galat }}">{{ $message }}</span> @enderror

                                                <div class="flex flex-wrap gap-2">
                                                    <button type="button" wire:click="simpanGantiFoto" wire:loading.attr="disabled"
                                                        class="rounded-ta border-[1.5px] border-ink bg-mint px-3 py-1.5 text-xs font-semibold">
                                                        Simpan Perubahan Foto
                                                    </button>
                                                    <button type="button" wire:click="batalGantiFoto"
                                                        class="rounded-ta border border-ink bg-paper px-3 py-1.5 text-xs font-semibold">
                                                        Batal
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex flex-wrap items-center gap-3">
                                                <img src="{{ $lampiran->url() }}" alt="" class="size-16 shrink-0 rounded-lg border border-ink object-cover">
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate text-xs font-semibold">{{ $lampiran->keterangan ?: 'Tanpa keterangan' }}</div>
                                                    <div class="text-[11px] text-muted">{{ number_format($lampiran->ukuran_file / 1024, 0) }} KB</div>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button" wire:click="mulaiGantiFoto({{ $lampiran->id }})"
                                                        class="rounded-ta border border-ink bg-paper px-2.5 py-1 text-xs font-semibold hover:bg-cream">
                                                        Ganti
                                                    </button>
                                                    <button type="button" wire:click="hapusLampiran({{ $lampiran->id }})"
                                                        class="rounded-ta border border-ink bg-coral px-2.5 py-1 text-xs font-semibold">
                                                        Hapus
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Input Unggah Foto Baru --}}
                        <div class="grid gap-2">
                            <label for="fotoBaru" class="text-xs font-semibold">Tambah Foto Baru (bisa pilih lebih dari satu)</label>
                            <input id="fotoBaru" type="file" wire:model="fotoBaru" multiple accept="image/jpeg,image/png,image/webp"
                                class="block w-full rounded-ta border-[1.5px] border-ink bg-paper p-2 text-xs file:mr-3 file:rounded-ta file:border file:border-ink file:bg-yellow file:px-3 file:py-1.5 file:font-semibold">
                            <div wire:loading wire:target="fotoBaru" class="text-xs font-semibold text-muted">Memuat pratinjau foto…</div>
                            @error('fotoBaru') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                            @error('fotoBaru.*') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                        </div>

                        {{-- Pratinjau Foto Baru Sebelum Disimpan --}}
                        @if (! empty($fotoBaru))
                            <div class="grid gap-2.5">
                                @foreach ($fotoBaru as $idx => $preview)
                                    <div wire:key="preview-baru-{{ $idx }}" class="flex flex-wrap items-center gap-3 rounded-ta border border-ink bg-paper p-2.5">
                                        @if (method_exists($preview, 'temporaryUrl'))
                                            <img src="{{ $preview->temporaryUrl() }}" alt="" class="size-14 shrink-0 rounded-lg border border-ink object-cover">
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <input type="text" wire:model="keteranganFotoBaru.{{ $idx }}"
                                                placeholder="Tulis keterangan foto ini (opsional)…"
                                                class="{{ $input }}">
                                        </div>
                                        <button type="button" wire:click="hapusAntreanFoto({{ $idx }})"
                                            class="rounded-ta border border-ink bg-coral px-2.5 py-1.5 text-xs font-semibold">
                                            Batal
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t-[1.5px] border-ink bg-cream -mx-5 -mb-5 mt-2 px-5 py-4">
                        <button type="button" wire:click="batalForm"
                            class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold hover:bg-cream">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                            <span wire:loading.remove wire:target="simpanLaporan">Simpan Laporan</span>
                            <span wire:loading wire:target="simpanLaporan">Menyimpan…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Konfirmasi Hapus Laporan Harian (User Story 3) --}}
    @if ($laporanAkanDihapus)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/55 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-hapus-judul">
            <div class="w-full max-w-md overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                <div class="flex items-center justify-between gap-3 border-b-[1.5px] border-ink bg-coral px-5 py-4">
                    <h2 id="modal-hapus-judul" class="text-lg font-bold">Hapus Laporan Harian?</h2>
                    <button type="button" wire:click="batalHapus" aria-label="Tutup"
                        class="grid size-9 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-sm font-bold hover:bg-cream">
                        ✕
                    </button>
                </div>

                <div class="grid gap-3 p-5 text-sm">
                    <p>
                        Anda yakin ingin menghapus laporan harian milik
                        <strong>{{ $laporanAkanDihapus->anak?->nama_lengkap ?? 'anak ini' }}</strong>
                        tanggal <strong>{{ $tanggalObjek->translatedFormat('d M Y') }}</strong>?
                    </p>
                    @if ($laporanAkanDihapus->lampiran->isNotEmpty())
                        <p class="rounded-lg border border-ink/40 bg-cream px-3 py-2 text-xs font-medium">
                            Seluruh <strong>{{ $laporanAkanDihapus->lampiran->count() }} foto kegiatan</strong> pada laporan ini juga akan ikut dihapus secara permanen.
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t-[1.5px] border-ink bg-cream px-5 py-4">
                    <button type="button" wire:click="batalHapus"
                        class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold hover:bg-cream">
                        Batal
                    </button>
                    <button type="button" wire:click="hapusLaporan" wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-coral px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                        <span wire:loading.remove wire:target="hapusLaporan">Ya, Hapus Laporan</span>
                        <span wire:loading wire:target="hapusLaporan">Menghapus…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>