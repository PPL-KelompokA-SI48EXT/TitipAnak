@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $galat = 'text-xs font-semibold text-[#B02E12]';
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Presensi</h1>
        <p class="text-muted">Catat kedatangan anak di kelompok yang Anda pegang hari ini · {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}</p>
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

    {{-- Form check-in --}}
    @if ($terpilih)
        <form wire:submit="simpan" class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-6" aria-labelledby="judul-checkin">
            <h2 id="judul-checkin" class="text-xl font-bold">Check-in: {{ $terpilih->nama_lengkap }}</h2>

            <div class="grid gap-1.5 sm:max-w-48">
                <label for="jam_datang" class="text-sm font-semibold">Jam datang</label>
                <input id="jam_datang" type="time" wire:model="jam_datang" class="{{ $input }}">
                @error('jam_datang') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-1.5">
                <label for="kondisi" class="text-sm font-semibold">Kondisi anak saat datang</label>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Pilihan cepat kondisi">
                    @foreach ($kondisiList as $k)
                        <button type="button" wire:click="pakaiKondisi('{{ $k }}')" aria-pressed="{{ $kondisi === $k ? 'true' : 'false' }}"
                            class="rounded-full border-[1.5px] border-ink px-3 py-1.5 text-xs font-semibold transition {{ $kondisi === $k ? 'bg-ink text-paper' : 'bg-paper hover:bg-cream' }}">{{ $k }}</button>
                    @endforeach
                </div>
                <textarea id="kondisi" wire:model="kondisi" rows="2" maxlength="255" placeholder="Tulis kondisi anak saat datang" class="{{ $input }}"></textarea>
                @error('kondisi') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                    <span wire:loading.remove wire:target="simpan">Simpan check-in</span>
                    <span wire:loading wire:target="simpan">Menyimpan…</span>
                </button>
                <button type="button" wire:click="batal"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-5 py-3 text-sm font-semibold transition hover:bg-cream">Batal</button>
            </div>
        </form>
    @endif

    {{-- Daftar anak --}}
    @if ($anak->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada anak untuk dicatat</div>
            <p class="mt-1 text-sm text-muted">Anda tidak punya shift hari ini, atau kelompok usia Anda belum punya anak aktif.</p>
        </div>
    @else
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($anak as $a)
                @php $hadir = $a->kehadiran->first(); @endphp
                <li wire:key="anak-{{ $a->id }}" class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-paper p-4">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full border border-ink font-bold {{ $palet[$loop->index % count($palet)] }}" aria-hidden="true">{{ mb_strtoupper(mb_substr($a->nama_lengkap, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <div class="break-words font-semibold">{{ $a->nama_lengkap }}</div>
                            <div class="text-xs text-muted">{{ $a->kelompokUsia?->nama }}</div>
                        </div>
                    </div>

                    @if ($hadir)
                        <span class="rounded-full border border-ink bg-mint px-3 py-1 text-center text-xs font-semibold">
                            Sudah check-in {{ $hadir->check_in_pada->format('H:i') }}
                        </span>
                    @else
                        <button type="button" wire:click="mulaiCheckIn({{ $a->id }})"
                            class="rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5"
                            aria-label="Check-in {{ $a->nama_lengkap }}">Check-in</button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
