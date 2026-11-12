@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $galat = 'text-xs font-semibold text-[#B02E12]';
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Check-out</h1>
        <p class="text-muted">Catat kepulangan anak dan pastikan dijemput orang yang terdaftar · {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}</p>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true">✅</span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    @if (session('galat'))
        <div role="alert" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-coral px-4 py-3 text-sm">
            <span aria-hidden="true">⚠️</span>
            <span><b>Tidak bisa dicatat.</b> {{ session('galat') }}</span>
        </div>
    @endif

    {{-- Form check-out --}}
    @if ($terpilih)
        <form wire:submit="simpan" class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-6" aria-labelledby="judul-checkout">
            <h2 id="judul-checkout" class="text-xl font-bold">Check-out: {{ $terpilih->anak->nama_lengkap }}</h2>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <label for="jam_pulang" class="text-sm font-semibold">Jam pulang</label>
                    <input id="jam_pulang" type="time" wire:model="jam_pulang" class="{{ $input }}">
                    @error('jam_pulang') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                </div>

                <div class="grid gap-1.5">
                    <label for="penjemput" class="text-sm font-semibold">Dijemput oleh</label>
                    <select id="penjemput" wire:model.live="penjemput" class="{{ $input }}">
                        <option value="">Pilih penjemput</option>
                        @foreach ($penjemputList as $p)
                            <option value="{{ $p->id }}">{{ $p->nama_lengkap }} ({{ $p->hubungan }})</option>
                        @endforeach
                        <option value="{{ \App\Livewire\Pengasuh\CheckOut::LAIN }}">Penjemput lain (tidak ada di daftar)</option>
                    </select>
                    @error('penjemput') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                    @if ($penjemputList->isEmpty())
                        <span class="text-xs text-muted">Orang tua belum mendaftarkan penjemput untuk anak ini.</span>
                    @endif
                </div>
            </div>

            @if ($penjemput === \App\Livewire\Pengasuh\CheckOut::LAIN)
                <div class="grid gap-1.5">
                    <label for="nama_penjemput" class="text-sm font-semibold">Nama penjemput</label>
                    <input id="nama_penjemput" type="text" wire:model="nama_penjemput" maxlength="100" placeholder="Nama lengkap sesuai identitas" class="{{ $input }}">
                    @error('nama_penjemput') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                    <span wire:loading.remove wire:target="simpan">Simpan check-out</span>
                    <span wire:loading wire:target="simpan">Menyimpan…</span>
                </button>
                <button type="button" wire:click="batal"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-5 py-3 text-sm font-semibold transition hover:bg-cream">Batal</button>
            </div>
        </form>
    @endif

    {{-- Anak yang masih di tempat --}}
    @if ($daftar->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada anak yang menunggu dijemput</div>
            <p class="mt-1 text-sm text-muted">Anak muncul di sini setelah check-in dan sebelum check-out.</p>
        </div>
    @else
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($daftar as $k)
                <li wire:key="kehadiran-{{ $k->id }}" class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-paper p-4">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full border border-ink font-bold {{ $palet[$loop->index % count($palet)] }}" aria-hidden="true">{{ mb_strtoupper(mb_substr($k->anak->nama_lengkap, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <div class="break-words font-semibold">{{ $k->anak->nama_lengkap }}</div>
                            <div class="text-xs text-muted">{{ $k->anak->kelompokUsia?->nama }} · datang {{ $k->check_in_pada->format('H:i') }}</div>
                        </div>
                    </div>
                    <button type="button" wire:click="mulaiCheckOut({{ $k->id }})"
                        class="rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5"
                        aria-label="Check-out {{ $k->anak->nama_lengkap }}">Check-out</button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
