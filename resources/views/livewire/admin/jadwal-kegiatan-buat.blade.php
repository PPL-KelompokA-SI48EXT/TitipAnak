@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $galat = 'text-xs font-semibold text-[#B02E12]';
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">{{ $judul }}</h1>
        <p class="text-muted">{{ $subjudul }}</p>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true">✅</span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    <form wire:submit="simpan" class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-6">
        @if ($preset)
        <div class="grid gap-1.5">
            <span class="text-sm font-semibold">Kegiatan rutin</span>
            <div class="flex flex-wrap gap-2" role="group" aria-label="Pilih kegiatan rutin">
                @foreach ($preset as $nama)
                    <button type="button" wire:click="pakaiPreset('{{ $nama }}')" aria-pressed="{{ $nama_kegiatan === $nama ? 'true' : 'false' }}"
                        class="rounded-full border-[1.5px] border-ink px-4 py-2 text-sm font-semibold transition {{ $nama_kegiatan === $nama ? 'bg-ink text-paper' : 'bg-paper hover:bg-cream' }}">
                        {{ $nama }}
                    </button>
                @endforeach
            </div>
            <span class="text-xs text-muted">Klik untuk mengisi nama, jenis, dan jam otomatis. Tetap bisa diubah.</span>
        </div>
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="grid gap-1.5">
                <label for="kelompok_usia_id" class="text-sm font-semibold">Kelompok usia</label>
                <select id="kelompok_usia_id" wire:model="kelompok_usia_id" class="{{ $input }}">
                    <option value="">Pilih kelompok usia</option>
                    @foreach ($kelompok as $k)
                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
                    @endforeach
                </select>
                @error('kelompok_usia_id') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-1.5">
                <label for="hari" class="text-sm font-semibold">Hari</label>
                <select id="hari" wire:model="hari" class="{{ $input }}">
                    <option value="">Setiap hari</option>
                    @foreach ($hariList as $nomor => $nama)
                        <option value="{{ $nomor }}">{{ $nama }}</option>
                    @endforeach
                </select>
                @error('hari') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="grid gap-1.5">
                <label for="nama_kegiatan" class="text-sm font-semibold">Nama kegiatan</label>
                <input id="nama_kegiatan" type="text" wire:model="nama_kegiatan" maxlength="100" placeholder="mis. Makan siang" class="{{ $input }}">
                @error('nama_kegiatan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-1.5">
                <label for="jenis_kegiatan" class="text-sm font-semibold">Jenis kegiatan</label>
                <select id="jenis_kegiatan" wire:model="jenis_kegiatan" class="{{ $input }}">
                    <option value="">Pilih jenis</option>
                    @foreach ($jenisList as $kode => $label)
                        <option value="{{ $kode }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('jenis_kegiatan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
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

        <div class="grid gap-1.5">
            <label for="catatan" class="text-sm font-semibold">Catatan <span class="font-normal text-muted">(opsional)</span></label>
            <textarea id="catatan" wire:model="catatan" rows="3" maxlength="500" placeholder="mis. Siapkan makanan tanpa kacang" class="{{ $input }}"></textarea>
            @error('catatan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                <span wire:loading.remove wire:target="simpan">{{ $tombol }}</span>
                <span wire:loading wire:target="simpan">Menyimpan…</span>
            </button>
        </div>
    </form>
</div>
