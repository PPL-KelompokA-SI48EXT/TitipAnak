<div class="grid gap-6">
    <div>
        <h1 class="text-4xl font-bold leading-tight">Buat Pendaftaran</h1>
        <p class="text-muted">Pilih paket layanan untuk memulai pendaftaran. Pendaftaran dimulai sebagai Draft.</p>
    </div>

    <form wire:submit="simpan" class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-6">
        <fieldset class="grid gap-3">
            <legend class="text-sm font-semibold">Paket layanan</legend>

            @forelse ($paket as $p)
                <label class="flex cursor-pointer gap-3 rounded-ta border-[1.5px] border-ink bg-paper p-4 transition hover:bg-cream has-[:checked]:bg-yellow">
                    <input type="radio" wire:model="paket_layanan_id" value="{{ $p->id }}" class="mt-1 accent-ink">
                    <span class="grid gap-0.5">
                        <span class="font-semibold">{{ $p->nama }} · Rp {{ number_format($p->harga, 0, ',', '.') }}</span>
                        <span class="text-sm text-muted">{{ $p->deskripsi }}</span>
                    </span>
                </label>
            @empty
                <p class="rounded-ta border-[1.5px] border-dashed border-ink bg-cream p-4 text-sm">Belum ada paket layanan yang tersedia.</p>
            @endforelse

            @error('paket_layanan_id') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
        </fieldset>

        <div class="flex flex-wrap gap-3">
            <button type="submit"
                class="rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
                Simpan sebagai Draft
            </button>
            <a href="{{ route('orang-tua.pendaftaran.index') }}"
                class="inline-flex items-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-3 text-sm font-semibold transition hover:bg-cream">
                Batal
            </a>
        </div>
    </form>
</div>
