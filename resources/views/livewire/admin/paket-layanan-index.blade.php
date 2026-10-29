<div class="grid gap-8">
    <header>
        <h1 class="text-3xl font-bold tracking-tight text-ink">Paket Layanan</h1>
        <p class="mt-1 max-w-2xl text-sm text-muted">Tambahkan pilihan layanan yang dapat dipilih orang tua.</p>
    </header>

    @if (session('sukses'))
        <div role="status" aria-live="polite" class="rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm text-ink">
            {{ session('sukses') }}
        </div>
    @endif

    <section class="rounded-ta border-[1.5px] border-ink bg-cream p-5 sm:p-6" aria-labelledby="form-paket-heading">
        <div class="mb-5">
            <h2 id="form-paket-heading" class="text-xl font-bold text-ink">{{ $editingId ? 'Edit paket' : 'Tambah paket' }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $editingId ? 'Sesuaikan tarif, deskripsi, atau status paket.' : 'Isi detail paket dan tarif bulanan atau harian.' }}</p>
        </div>

        <form wire:submit="{{ $editingId ? 'simpanEdit' : 'simpan' }}" class="grid gap-5">
            @if ($editingId)
                <div class="grid gap-1.5">
                    <span class="text-sm font-semibold text-ink">Nama paket</span>
                    <div class="rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink" aria-readonly="true">
                        {{ $paketLayanan->firstWhere('id', $editingId)?->nama }}
                    </div>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                @if (! $editingId)
                    <div class="grid gap-1.5">
                        <label for="nama" class="text-sm font-semibold text-ink">Nama paket</label>
                        <input id="nama" type="text" wire:model="nama" maxlength="100" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" autocomplete="off">
                        @error('nama') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="grid gap-1.5">
                    <label for="{{ $editingId ? 'editHarga' : 'harga' }}" class="text-sm font-semibold text-ink">Tarif</label>
                    <input id="{{ $editingId ? 'editHarga' : 'harga' }}" type="number" wire:model="{{ $editingId ? 'editHarga' : 'harga' }}" min="1" max="9999999999.99" step="0.01" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" inputmode="decimal">
                @if (! $editingId)
                    @error('harga') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                @else
                    @error('editHarga') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                @endif
                </div>

                @if ($editingId)
                    <label for="editAktif" class="flex min-h-11 items-center gap-3 rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-sm font-semibold text-ink">
                        <input id="editAktif" type="checkbox" wire:model="editAktif" class="h-5 w-5 accent-yellow focus-visible:outline-3 focus-visible:outline-ink">
                        Paket aktif
                    </label>
                    @error('editAktif') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                @endif
            </div>

            @if (! $editingId)
                <div class="grid gap-1.5">
                    <label for="deskripsi" class="text-sm font-semibold text-ink">Deskripsi</label>
                    <textarea id="deskripsi" wire:model="deskripsi" rows="3" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" placeholder="Jelaskan isi dan jadwal layanan…"></textarea>
                    @error('deskripsi') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                </div>
            @else
                <div class="grid gap-1.5">
                    <label for="editDeskripsi" class="text-sm font-semibold text-ink">Deskripsi</label>
                    <textarea id="editDeskripsi" wire:model="editDeskripsi" rows="3" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" placeholder="Jelaskan isi dan jadwal layanan…"></textarea>
                    @error('editDeskripsi') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled" wire:target="{{ $editingId ? 'simpanEdit' : 'simpan' }}" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="{{ $editingId ? 'simpanEdit' : 'simpan' }}">{{ $editingId ? 'Simpan perubahan' : 'Simpan paket' }}</span>
                    <span wire:loading wire:target="{{ $editingId ? 'simpanEdit' : 'simpan' }}">Menyimpan…</span>
                </button>
                @if ($editingId)
                    <button type="button" wire:click="batalEdit" wire:loading.attr="disabled" wire:target="batalEdit" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                @endif
            </div>
        </form>
    </section>

    <section aria-labelledby="daftar-paket-heading">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="daftar-paket-heading" class="text-xl font-bold text-ink">Daftar paket</h2>
                <p class="mt-1 text-sm text-muted">Paket aktif tampil lebih dulu untuk memudahkan pengelolaan.</p>
            </div>
            <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $paketLayanan->count() }} paket</span>
        </div>

        @if ($paketLayanan->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                Belum ada paket layanan.
            </div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar paket layanan</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nama paket</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Deskripsi</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Tarif</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Status</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paketLayanan as $paket)
                            <tr wire:key="paket-{{ $paket->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="px-4 py-3 font-semibold text-ink">{{ $paket->nama }}</td>
                                <td class="max-w-sm px-4 py-3 text-muted">{{ $paket->deskripsi ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">Rp {{ number_format((float) $paket->harga, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full border border-ink px-3 py-1 text-xs font-semibold text-ink {{ $paket->aktif ? 'bg-mint' : 'bg-coral' }}">
                                        {{ $paket->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="mulaiEdit({{ $paket->id }})" wire:loading.attr="disabled" wire:target="mulaiEdit({{ $paket->id }})" class="inline-flex min-h-11 items-center rounded-ta border-[1.5px] border-ink bg-yellow px-3 py-2 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Edit</button>
                                        <button type="button" wire:click="toggleAktif({{ $paket->id }})" wire:loading.attr="disabled" wire:target="toggleAktif({{ $paket->id }})" aria-pressed="{{ $paket->aktif ? 'true' : 'false' }}" class="inline-flex min-h-11 items-center rounded-ta border-[1.5px] border-ink bg-paper px-3 py-2 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                                            {{ $paket->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
