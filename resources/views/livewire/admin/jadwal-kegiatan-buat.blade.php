@php
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $galat = 'text-xs font-semibold text-[#B02E12]';
    $palet = ['bg-yellow', 'bg-blue', 'bg-mint', 'bg-lavender', 'bg-coral'];
    $warnaJenis = ['makan' => 'bg-yellow', 'bermain' => 'bg-blue', 'belajar' => 'bg-lavender', 'tidur' => 'bg-mint', 'lainnya' => 'bg-cream'];
    $ikonJenis = ['makan' => '🍽️', 'bermain' => '🧩', 'belajar' => '📚', 'tidur' => '😴', 'lainnya' => '⭐'];
    $ikonHari = [1 => '🌱', 2 => '🌼', 3 => '🌈', 4 => '🍀', 5 => '🎉', 6 => '🧸', 7 => '☀️'];
    $namaKelompok = $kelompok->firstWhere('id', (int) $kelompok_usia_id)?->nama;
@endphp

<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">{{ $judul }}</h1>
        <p class="text-muted">{{ $subjudul }}</p>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="✅" /></span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    <form wire:submit="simpan" class="grid gap-5">
        <x-form.bagian nomor="1" judul="Untuk kelompok usia mana?" hint="Rutinitas dibuat per kelompok usia." warna="bg-mint">
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4" role="radiogroup" aria-label="Kelompok usia">
                @foreach ($kelompok as $k)
                    <x-form.pilihan wire:model.live="kelompok_usia_id" :value="$k->id" :warna="$palet[$loop->index % count($palet)]">
                        <span class="grid min-w-0">
                            <span>{{ $k->nama }}</span>
                            @if (isset($k->usia_min_bulan))<span class="text-xs font-normal opacity-70">{{ $k->usia_min_bulan }}–{{ $k->usia_maks_bulan }} bulan</span>@endif
                        </span>
                    </x-form.pilihan>
                @endforeach
            </div>
            @error('kelompok_usia_id') <span class="{{ $galat }}">{{ $message }}</span> @enderror
        </x-form.bagian>

        <x-form.bagian nomor="2" judul="Kegiatannya apa?" hint="Pakai kegiatan rutin atau tulis sendiri." warna="bg-yellow">
            @if ($preset)
                <div class="grid gap-2">
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

            <div class="grid gap-1.5">
                <label for="nama_kegiatan" class="text-sm font-semibold">Nama kegiatan</label>
                <input id="nama_kegiatan" type="text" wire:model.live.debounce.400ms="nama_kegiatan" maxlength="100" placeholder="mis. Makan siang" class="{{ $input }}">
                @error('nama_kegiatan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-2">
                <span class="text-sm font-semibold" id="label-jenis">Jenis kegiatan</span>
                <div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="label-jenis">
                    @foreach ($jenisList as $kode => $label)
                        <x-form.pilihan wire:model.live="jenis_kegiatan" :value="$kode" :warna="$warnaJenis[$kode] ?? 'bg-cream'"><x-ikon :e="$ikonJenis[$kode] ?? '⭐'" /> {{ $label }}</x-form.pilihan>
                    @endforeach
                </div>
                @error('jenis_kegiatan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>
        </x-form.bagian>

        <x-form.bagian nomor="3" judul="Kapan dilakukan?" hint="Pilih hari dan atur jam kegiatan." warna="bg-blue">
            <div class="grid gap-2">
                <span class="text-sm font-semibold" id="label-hari">Hari</span>
                <div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="label-hari">
                    <x-form.pilihan wire:model.live="hari" value=""><x-ikon e="🔁" /> Setiap hari</x-form.pilihan>
                    @foreach ($hariList as $nomor => $nama)
                        <x-form.pilihan wire:model.live="hari" :value="$nomor"><x-ikon :e="$ikonHari[$nomor]" /> {{ $nama }}</x-form.pilihan>
                    @endforeach
                </div>
                @error('hari') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <label for="jam_mulai" class="text-sm font-semibold">Jam mulai</label>
                    <input id="jam_mulai" type="time" wire:model.live.debounce.400ms="jam_mulai" class="{{ $input }}">
                    @error('jam_mulai') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                </div>
                <div class="grid gap-1.5">
                    <label for="jam_selesai" class="text-sm font-semibold">Jam selesai</label>
                    <input id="jam_selesai" type="time" wire:model.live.debounce.400ms="jam_selesai" class="{{ $input }}">
                    @error('jam_selesai') <span class="{{ $galat }}">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-form.bagian>

        <x-form.bagian nomor="4" judul="Catatan tambahan" hint="Boleh dikosongkan." warna="bg-lavender">
            <div class="grid gap-1.5">
                <label for="catatan" class="text-sm font-semibold">Catatan <span class="font-normal text-muted">(opsional)</span></label>
                <textarea id="catatan" wire:model="catatan" rows="3" maxlength="500" placeholder="mis. Siapkan makanan tanpa kacang" class="{{ $input }}"></textarea>
                @error('catatan') <span class="{{ $galat }}">{{ $message }}</span> @enderror
            </div>
        </x-form.bagian>

        {{-- Pratinjau seperti tampilan di jadwal --}}
        <div class="grid gap-2" aria-live="polite">
            <span class="text-sm font-semibold">Pratinjau</span>
            <div class="grid gap-2 sm:grid-cols-[8.5rem_1fr] sm:gap-4">
                <div class="sm:pt-3">
                    <div class="whitespace-nowrap text-sm font-bold">{{ $jam_mulai ?: '--:--' }} – {{ $jam_selesai ?: '--:--' }}</div>
                    <div class="text-xs text-muted">{{ $hari !== '' ? ($hariList[$hari] ?? '') : 'Setiap hari' }}@if ($namaKelompok) · {{ $namaKelompok }}@endif</div>
                </div>
                <div class="rounded-ta border-[1.5px] border-ink px-4 py-3 {{ $warnaJenis[$jenis_kegiatan] ?? 'bg-cream' }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <span aria-hidden="true"><x-ikon :e="$ikonJenis[$jenis_kegiatan] ?? '⭐'" /></span>
                        <span class="font-bold">{{ $nama_kegiatan !== '' ? $nama_kegiatan : 'Nama kegiatan' }}</span>
                        @if ($jenis_kegiatan !== '' && isset($jenisList[$jenis_kegiatan]))
                            <span class="rounded-full border border-ink bg-paper px-3 py-0.5 text-xs font-semibold">{{ $jenisList[$jenis_kegiatan] }}</span>
                        @endif
                    </div>
                    @if ($catatan !== '')<p class="mt-1 text-sm">{{ $catatan }}</p>@endif
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-6 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-50">
                <span wire:loading.remove wire:target="simpan">{{ $tombol }}</span>
                <span wire:loading wire:target="simpan">Menyimpan…</span>
            </button>
            <a href="{{ route('admin.jadwal-kegiatan.index') }}" class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-6 py-3 text-sm font-semibold transition hover:bg-cream">Batal</a>
        </div>
    </form>
</div>
