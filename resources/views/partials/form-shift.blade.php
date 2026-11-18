@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $galat = 'text-xs font-semibold text-[#B02E12]';
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
    $ikonHari = [1 => '🌱', 2 => '🌼', 3 => '🌈', 4 => '🍀', 5 => '🎉', 6 => '🧸', 7 => '☀️'];
    $paket = ['Pagi' => ['07:00', '12:00'], 'Sehari' => ['07:00', '15:00'], 'Sore' => ['12:00', '18:00']];
@endphp

<form wire:submit="simpan" class="grid gap-5">
    <x-form.bagian nomor="1" judul="Siapa pengasuhnya?" hint="Pilih pengasuh yang bertugas." warna="bg-blue">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3" role="radiogroup" aria-label="Pengasuh">
            @if (! empty($lamaNonaktif) && $pengasuhLama)
                <x-form.pilihan wire:model="pengasuh_id" :value="$pengasuhLama->id" warna="bg-cream">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full border border-ink bg-paper font-bold text-ink" aria-hidden="true">{{ mb_strtoupper(mb_substr($pengasuhLama->nama, 0, 1)) }}</span>
                    <span class="min-w-0 break-words">{{ $pengasuhLama->nama }} (nonaktif)</span>
                </x-form.pilihan>
            @endif
            @foreach ($pengasuh as $p)
                <x-form.pilihan wire:model="pengasuh_id" :value="$p->id">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full border border-ink font-bold text-ink {{ $palet[$loop->index % count($palet)] }}" aria-hidden="true">{{ mb_strtoupper(mb_substr($p->nama, 0, 1)) }}</span>
                    <span class="min-w-0 break-words">{{ $p->nama }}</span>
                </x-form.pilihan>
            @endforeach
        </div>
        @error('pengasuh_id') <span class="{{ $galat }}">{{ $message }}</span> @enderror
    </x-form.bagian>

    <x-form.bagian nomor="2" judul="Menjaga kelompok usia mana?" hint="Satu shift untuk satu kelompok usia." warna="bg-mint">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4" role="radiogroup" aria-label="Kelompok usia">
            @foreach ($kelompok as $k)
                <x-form.pilihan wire:model="kelompok_usia_id" :value="$k->id" :warna="$palet[$loop->index % count($palet)]">
                    <span class="grid min-w-0">
                        <span>{{ $k->nama }}</span>
                        @if (isset($k->usia_min_bulan))<span class="text-xs font-normal opacity-70">{{ $k->usia_min_bulan }}–{{ $k->usia_maks_bulan }} bulan</span>@endif
                    </span>
                </x-form.pilihan>
            @endforeach
        </div>
        @error('kelompok_usia_id') <span class="{{ $galat }}">{{ $message }}</span> @enderror
    </x-form.bagian>

    <x-form.bagian nomor="3" judul="Hari dan jam bertugas" hint="Pilih hari lalu atur jam shift." warna="bg-yellow">
        <div class="grid gap-2">
            <span class="text-sm font-semibold" id="label-hari">Hari</span>
            <div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="label-hari">
                @foreach ($hariList as $nomor => $nama)
                    <x-form.pilihan wire:model="hari" :value="$nomor"><x-ikon :e="$ikonHari[$nomor]" /> {{ $nama }}</x-form.pilihan>
                @endforeach
            </div>
            @error('hari') <span class="{{ $galat }}">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-2">
            <span class="text-sm font-semibold">Pilihan cepat</span>
            <div class="flex flex-wrap gap-2">
                @foreach ($paket as $nama => [$mulai, $selesai])
                    <button type="button" x-data x-on:click="$wire.jam_mulai = '{{ $mulai }}'; $wire.jam_selesai = '{{ $selesai }}'"
                        class="rounded-full border-[1.5px] border-ink bg-paper px-4 py-1.5 text-xs font-semibold transition hover:bg-cream"><x-ikon e="⏰" /> {{ $nama }} · {{ $mulai }}–{{ $selesai }}</button>
                @endforeach
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-1.5">
                <label for="jam_mulai" class="text-sm font-semibold">Jam mulai</label>
                <input id="jam_mulai" type="time" wire:model="jam_mulai" class="{{ $input }}">
                @error('jam_mulai') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>
            <div class="grid gap-1.5">
                <label for="jam_selesai" class="text-sm font-semibold">Jam selesai</label>
                <input id="jam_selesai" type="time" wire:model="jam_selesai" class="{{ $input }}">
                @error('jam_selesai') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>
        </div>
    </x-form.bagian>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" wire:loading.attr="disabled"
            class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-6 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
            <span wire:loading.remove wire:target="simpan">{{ $tombol }}</span>
            <span wire:loading wire:target="simpan">Menyimpan…</span>
        </button>
        @isset($batalUrl)
            <a href="{{ $batalUrl }}" class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-6 py-3 text-sm font-semibold transition hover:bg-cream">Batal</a>
        @endisset
    </div>
</form>
