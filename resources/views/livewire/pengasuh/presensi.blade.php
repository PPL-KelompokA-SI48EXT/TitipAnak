@php
    $label = ['belum' => 'Belum datang', 'hadir' => 'Hadir', 'pulang' => 'Sudah pulang'];
    $warna = ['belum' => 'bg-cream', 'hadir' => 'bg-mint', 'pulang' => 'bg-blue'];
    $input = 'rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $adaFilter = $q !== '' || $statusAktif !== '';
    $galat = 'text-xs font-semibold text-[#B02E12]';
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Presensi</h1>
        <p class="text-muted">Catat kedatangan dan kepulangan anak di kelompok yang Anda pegang hari ini · {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}</p>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="✅" /></span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    @if (session('galat'))
        <div role="alert" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-coral px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="⚠" /></span>
            <span><b>Tidak bisa dicatat.</b> {{ session('galat') }}</span>
        </div>
    @endif

    @if ($semua > 0)
        {{-- Ringkasan status (klik untuk menyaring) --}}
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter status kehadiran">
            @foreach ($label as $kode => $nama)
                <button type="button" wire:click="pilihStatus('{{ $kode }}')" aria-pressed="{{ $statusAktif === $kode ? 'true' : 'false' }}"
                    class="rounded-full border-[1.5px] border-ink px-4 py-2 text-sm font-semibold transition {{ $statusAktif === $kode ? 'bg-ink text-paper' : $warna[$kode].' hover:-translate-y-0.5' }}">
                    {{ $nama }} · {{ $jumlah[$kode] }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label class="relative min-w-56 flex-1">
                <span class="sr-only">Cari nama anak</span>
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true"><x-ikon e="🔍" /></span>
                <input type="search" wire:model.live.debounce.300ms="q" placeholder="Cari nama anak…" autocomplete="off"
                    class="{{ $input }} w-full pl-10">
            </label>

            @if ($adaFilter)
                <button type="button" wire:click="resetFilter"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2.5 text-sm font-semibold transition hover:bg-cream">Reset filter</button>
            @endif
        </div>

        <p class="-mt-3 text-sm text-muted" aria-live="polite">Menampilkan {{ $anak->count() }} dari {{ $semua }} anak</p>
    @endif

    @if ($semua === 0)
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada anak untuk hari ini</div>
            <p class="mt-1 text-sm text-muted">Anda tidak punya shift hari ini, atau kelompok usia Anda belum punya anak aktif.</p>
        </div>
    @elseif ($anak->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada hasil</div>
            <p class="mt-1 text-sm text-muted">Coba kata kunci lain atau reset filter.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($anak as $a)
                @php
                    $p = $a->presensi_hari_ini;
                    $inisial = mb_strtoupper(mb_substr($a->nama_lengkap, 0, 1));
                    $ikon = ['belum' => '🕗', 'hadir' => '🧒', 'pulang' => '🏠'];
                @endphp
                <article wire:key="anak-{{ $a->id }}" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper">
                    <header class="flex items-start gap-3 border-b-[1.5px] border-ink px-4 py-3 {{ $warna[$a->status_hari_ini] }}">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-lg font-bold" aria-hidden="true">{{ $inisial }}</span>
                        <div class="min-w-0 flex-1">
                            <h2 class="break-words text-lg font-bold leading-tight">{{ $a->nama_lengkap }}</h2>
                            <p class="text-xs text-muted">Kelompok {{ $a->kelompokUsia?->nama ?? '—' }}</p>
                            <span class="mt-1.5 inline-block whitespace-nowrap rounded-full border border-ink bg-paper px-3 py-0.5 text-xs font-semibold"><x-ikon :e="$ikon[$a->status_hari_ini]" /> {{ $label[$a->status_hari_ini] }}</span>
                        </div>
                    </header>

                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 px-4 py-3 text-sm">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Jam datang</dt>
                            <dd class="text-base font-bold">{{ $p?->check_in_pada->format('H:i') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Jam pulang</dt>
                            <dd class="text-base font-bold">{{ $p?->check_out_pada?->format('H:i') ?? '—' }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Kondisi</dt>
                            <dd>{{ $p?->kondisi_check_in ?: '—' }}</dd>
                        </div>
                    </dl>

                    @if ($a->status_hari_ini !== 'pulang')
                        <footer class="border-t-[1.5px] border-ink bg-cream/60 px-4 py-2.5">
                            @if ($a->status_hari_ini === 'belum')
                                <button type="button" wire:click="mulaiCheckIn({{ $a->id }})" aria-label="Check-in {{ $a->nama_lengkap }}" class="inline-flex w-full items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5"><x-ikon e="✅" /> Check-in</button>
                            @else
                                <button type="button" wire:click="mulaiCheckOut({{ $p->id }})" aria-label="Check-out {{ $a->nama_lengkap }}" class="inline-flex w-full items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5 hover:bg-cream"><x-ikon e="🚪" /> Check-out</button>
                            @endif
                        </footer>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    {{-- Form check-in --}}
    @if ($terpilihCheckIn)
        @php
            $ikonKondisi = ['Sehat dan ceria' => '😊', 'Batuk atau pilek' => '🤧', 'Kurang tidur' => '😴', 'Rewel' => '😢', 'Demam ringan' => '🤒'];
        @endphp
        <x-dialog label="Form check-in">
        <form wire:submit="simpan" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="judul-checkin">
            <div class="flex items-center gap-4 border-b-[1.5px] border-ink bg-yellow px-5 py-4">
                <span class="grid size-14 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-2xl font-bold" aria-hidden="true">{{ mb_strtoupper(mb_substr($terpilihCheckIn->nama_lengkap, 0, 1)) }}</span>
                <div class="min-w-0">
                    <h2 id="judul-checkin" class="break-words text-xl font-bold leading-tight">Check-in: {{ $terpilihCheckIn->nama_lengkap }}</h2>
                    <p class="text-sm">Catat jam datang dan kondisi anak.</p>
                </div>
            </div>

            <div class="grid gap-6 p-5 sm:p-6">
                <div class="grid gap-1.5 sm:max-w-56">
                    <label for="jam_datang" class="text-sm font-semibold"><x-ikon e="⏰" /> Jam datang</label>
                    <input id="jam_datang" type="time" wire:model="jam_datang" class="{{ $input }} text-xl font-bold">
                    @error('jam_datang') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                </div>

                <div class="grid gap-2">
                    <label for="kondisi" class="text-sm font-semibold">Kondisi anak saat datang</label>
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Pilihan cepat kondisi">
                        @foreach ($kondisiList as $k)
                            <button type="button" wire:click="pakaiKondisi('{{ $k }}')" aria-pressed="{{ $kondisi === $k ? 'true' : 'false' }}"
                                class="rounded-full border-[1.5px] border-ink px-4 py-2 text-sm font-semibold transition {{ $kondisi === $k ? 'bg-ink text-paper' : 'bg-paper hover:bg-cream' }}"><span aria-hidden="true"><x-ikon :e="$ikonKondisi[$k] ?? '🙂'" /></span> {{ $k }}</button>
                        @endforeach
                    </div>
                    <textarea id="kondisi" wire:model="kondisi" rows="2" maxlength="255" placeholder="Tulis kondisi anak saat datang" class="{{ $input }}"></textarea>
                    @error('kondisi') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-3 border-t-[1.5px] border-ink bg-cream/60 px-5 py-4 sm:px-6">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-6 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                    <span wire:loading.remove wire:target="simpan">Simpan check-in</span>
                    <span wire:loading wire:target="simpan">Menyimpan…</span>
                </button>
                <button type="button" wire:click="batal"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-6 py-3 text-sm font-semibold transition hover:bg-cream">Batal</button>
            </div>
        </form>
        </x-dialog>
    @endif

    {{-- Form check-out --}}
    @if ($terpilih)
        <x-dialog label="Form check-out">
        <form wire:submit="simpan" class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper" aria-labelledby="judul-checkout">
            <div class="flex items-center gap-4 border-b-[1.5px] border-ink bg-blue px-5 py-4">
                <span class="grid size-14 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper text-2xl font-bold" aria-hidden="true">{{ mb_strtoupper(mb_substr($terpilih->anak->nama_lengkap, 0, 1)) }}</span>
                <div class="min-w-0">
                    <h2 id="judul-checkout" class="break-words text-xl font-bold leading-tight">Check-out: {{ $terpilih->anak->nama_lengkap }}</h2>
                    <p class="text-sm">Datang {{ $terpilih->check_in_pada->format('H:i') }} · pastikan dijemput orang yang berhak.</p>
                </div>
            </div>

            <div class="grid gap-6 p-5 sm:p-6">
                <div class="grid gap-1.5 sm:max-w-56">
                    <label for="jam_pulang" class="text-sm font-semibold"><x-ikon e="⏰" /> Jam pulang</label>
                    <input id="jam_pulang" type="time" wire:model="jam_pulang" class="{{ $input }} text-xl font-bold">
                    @error('jam_pulang') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                </div>

                <div class="grid gap-3">
                    <span class="text-sm font-semibold" id="label-penjemput">Dijemput oleh <span class="font-normal text-muted">· pilih penjemput</span></span>

                    @if ($penjemputList->isNotEmpty())
                        <div class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-muted">Penjemput terdaftar</span>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-labelledby="label-penjemput">
                                @foreach ($penjemputList as $p)
                                    <x-form.pilihan wire:model.live="penjemput" :value="$p->id" warna="bg-mint">
                                        <span aria-hidden="true"><x-ikon e="👪" /></span>
                                        <span class="min-w-0 break-words">{{ $p->nama_lengkap }} ({{ $p->hubungan }})</span>
                                    </x-form.pilihan>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($izinList->isNotEmpty())
                        <div class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-muted">Izin penjemputan disetujui (hari ini)</span>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-label="Izin penjemputan disetujui">
                                @foreach ($izinList as $i)
                                    <x-form.pilihan wire:model.live="penjemput" :value="\App\Livewire\Pengasuh\Presensi::IZIN.$i->id" warna="bg-lavender">
                                        <span aria-hidden="true"><x-ikon e="🪪" /></span>
                                        <span class="min-w-0 break-words">{{ $i->nama_penjemput }} (izin)</span>
                                    </x-form.pilihan>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-muted">Lainnya</span>
                        <div role="radiogroup" aria-label="Penjemput lain">
                            <x-form.pilihan wire:model.live="penjemput" :value="\App\Livewire\Pengasuh\Presensi::LAIN" warna="bg-cream">
                                <span aria-hidden="true"><x-ikon e="❓" /></span>
                                <span>Penjemput lain (tidak ada di daftar)</span>
                            </x-form.pilihan>
                        </div>
                    </div>

                    @error('penjemput') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    @if ($penjemputList->isEmpty() && $izinList->isEmpty())
                        <span class="text-xs text-muted">Orang tua belum mendaftarkan penjemput atau mengajukan izin penjemputan untuk anak ini.</span>
                    @endif
                </div>

                @if ($penjemput === \App\Livewire\Pengasuh\Presensi::LAIN)
                    <div class="grid gap-1.5">
                        <label for="nama_penjemput" class="text-sm font-semibold">Nama penjemput</label>
                        <input id="nama_penjemput" type="text" wire:model="nama_penjemput" maxlength="100" placeholder="Nama lengkap sesuai identitas" class="{{ $input }}">
                        @error('nama_penjemput') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    </div>
                @endif

            @if ($tidakCocok)
                <div role="alert" class="grid gap-2 rounded-ta border-[1.5px] border-ink bg-coral px-4 py-3 text-sm">
                    <div class="font-bold"><x-ikon e="⚠" /> Penjemput tidak cocok. Jangan lepas anak ini dulu.</div>
                    <ul class="grid list-disc gap-1 pl-5">
                        <li>
                            Penjemput yang boleh menjemput:
                            @if ($penjemputList->isEmpty())
                                <b>belum ada yang terdaftar</b>.
                            @else
                                <b>{{ $penjemputList->pluck('nama_lengkap')->join(', ') }}</b>.
                            @endif
                        </li>
                        <li>
                            Hubungi orang tua untuk memastikan:
                            @if ($kontakOrangTua)
                                <b>{{ $kontakOrangTua->nama_lengkap }}</b>, <a href="tel:{{ $kontakOrangTua->no_hp }}" class="font-semibold underline">{{ $kontakOrangTua->no_hp }}</a>.
                            @else
                                kontak orang tua belum tersedia, hubungi admin.
                            @endif
                        </li>
                        <li>Pastikan identitas penjemput sebelum mencoba lagi.</li>
                    </ul>
                </div>
            @endif

            </div>

            <div class="flex flex-wrap gap-3 border-t-[1.5px] border-ink bg-cream/60 px-5 py-4 sm:px-6">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-6 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                    <span wire:loading.remove wire:target="simpan">Simpan check-out</span>
                    <span wire:loading wire:target="simpan">Menyimpan…</span>
                </button>
                <button type="button" wire:click="batal"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-6 py-3 text-sm font-semibold transition hover:bg-cream">Batal</button>
            </div>
        </form>
        </x-dialog>
    @endif
</div>
