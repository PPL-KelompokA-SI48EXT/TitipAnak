<div class="grid gap-8">
    <header>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-ink">Tambah paket</h1>
                <p class="mt-1 max-w-2xl text-sm text-muted">Isi detail paket dan tarif bulanan atau harian.</p>
            </div>
            <a href="{{ route('admin.paket-layanan.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0">Kembali ke daftar</a>
        </div>
    </header>

    <section class="rounded-ta border-[1.5px] border-ink bg-cream p-5 sm:p-6" aria-labelledby="form-paket-heading">
        <div class="mb-5">
            <h2 id="form-paket-heading" class="text-xl font-bold text-ink">Detail paket</h2>
            <p class="mt-1 text-sm text-muted">Tambahkan nama, deskripsi, dan tarif layanan.</p>
        </div>

        <form wire:submit="simpan" class="grid gap-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <label for="nama" class="text-sm font-semibold text-ink">Nama paket</label>
                    <input id="nama" type="text" wire:model="nama" maxlength="100" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" autocomplete="off">
                    @error('nama') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                </div>

                <div class="grid gap-1.5">
                    <label for="harga" class="text-sm font-semibold text-ink">Tarif</label>
                    <input id="harga" type="number" wire:model="harga" min="1" max="9999999999.99" step="0.01" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" inputmode="decimal">
                    @error('harga') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid gap-1.5">
                <label for="deskripsi" class="text-sm font-semibold text-ink">Deskripsi</label>
                <textarea id="deskripsi" wire:model="deskripsi" rows="3" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink placeholder:text-muted focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" placeholder="Jelaskan isi dan jadwal layanan…"></textarea>
                @error('deskripsi') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
            </div>

            <div>
                <button type="submit" wire:loading.attr="disabled" wire:target="simpan" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="simpan">Simpan paket</span>
                    <span wire:loading wire:target="simpan">Menyimpan…</span>
                </button>
            </div>
        </form>
    </section>
</div>
