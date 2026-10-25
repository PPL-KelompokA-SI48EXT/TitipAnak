@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Buat Jadwal Shift</h1>
        <p class="text-muted">Hubungkan pengasuh dengan kelompok usia agar pembagian tugas jelas.</p>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true">✅</span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    <form wire:submit="simpan" class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-6">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="grid gap-1.5">
                <label for="pengasuh_id" class="text-sm font-semibold">Pengasuh</label>
                <select id="pengasuh_id" wire:model="pengasuh_id" class="{{ $input }}">
                    <option value="">Pilih pengasuh</option>
                    @foreach ($pengasuh as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
                @error('pengasuh_id') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-1.5">
                <label for="kelompok_usia_id" class="text-sm font-semibold">Kelompok usia</label>
                <select id="kelompok_usia_id" wire:model="kelompok_usia_id" class="{{ $input }}">
                    <option value="">Pilih kelompok usia</option>
                    @foreach ($kelompok as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
                @error('kelompok_usia_id') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div class="grid gap-1.5">
                <label for="hari" class="text-sm font-semibold">Hari</label>
                <select id="hari" wire:model="hari" class="{{ $input }}">
                    <option value="">Pilih hari</option>
                    @foreach ($hariList as $nomor => $nama)
                        <option value="{{ $nomor }}">{{ $nama }}</option>
                    @endforeach
                </select>
                @error('hari') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-1.5">
                <label for="jam_mulai" class="text-sm font-semibold">Jam mulai</label>
                <input id="jam_mulai" type="time" wire:model="jam_mulai" class="{{ $input }}">
                @error('jam_mulai') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-1.5">
                <label for="jam_selesai" class="text-sm font-semibold">Jam selesai</label>
                <input id="jam_selesai" type="time" wire:model="jam_selesai" class="{{ $input }}">
                @error('jam_selesai') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                <span wire:loading.remove wire:target="simpan">Simpan jadwal</span>
                <span wire:loading wire:target="simpan">Menyimpan…</span>
            </button>
        </div>
    </form>
</div>
