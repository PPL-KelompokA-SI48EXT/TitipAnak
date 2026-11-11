@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Tambah Kelompok Usia</h1>
        <p class="text-muted">Tambahkan kelompok usia baru untuk pengelompokan anak di daycare.</p>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true">✅</span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    <form wire:submit="simpan" class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-6">

        <div class="grid gap-1.5">
            <label for="nama" class="text-sm font-semibold">Nama kelompok usia</label>
            <input id="nama" type="text" wire:model="nama"
                placeholder="Contoh: Batita" maxlength="50" class="{{ $input }}">
            @error('nama') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="grid gap-1.5">
                <label for="usia_min_bulan" class="text-sm font-semibold">Usia minimum (bulan)</label>
                <input id="usia_min_bulan" type="number" min="0"
                    wire:model="usia_min_bulan" class="{{ $input }}">
                @error('usia_min_bulan') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-1.5">
                <label for="usia_maks_bulan" class="text-sm font-semibold">Usia maksimum (bulan)</label>
                <input id="usia_maks_bulan" type="number" min="0"
                    wire:model="usia_maks_bulan" class="{{ $input }}">
                @error('usia_maks_bulan') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid gap-1.5">
            <label for="kapasitas" class="text-sm font-semibold">Kapasitas anak</label>
            <input id="kapasitas" type="number" min="1"
                wire:model="kapasitas" class="{{ $input }}">
            @error('kapasitas') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-1.5">
            <label for="deskripsi" class="text-sm font-semibold">Deskripsi (opsional)</label>
            <textarea id="deskripsi" wire:model="deskripsi" rows="3"
                placeholder="Tambahkan keterangan kelompok usia..."
                class="{{ $input }}"></textarea>
            @error('deskripsi') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                <span wire:loading.remove wire:target="simpan">Simpan kelompok usia</span>
                <span wire:loading wire:target="simpan">Menyimpan…</span>
            </button>
        </div>
    </form>
</div>
