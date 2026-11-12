@props(['judul', 'tombol' => 'Hapus', 'aksi' => 'hapus', 'batal' => 'batalHapus'])

{{-- Dialog konfirmasi untuk aksi yang menghapus data. Isi dialog = slot. --}}
<div class="fixed inset-0 z-50 grid place-items-center bg-ink/50 p-4" role="dialog" aria-modal="true"
    aria-labelledby="dialog-judul" wire:keydown.escape.window="{{ $batal }}" wire:click.self="{{ $batal }}">
    <div class="grid w-full max-w-md gap-4 rounded-ta border-[1.5px] border-ink bg-paper p-6">
        <h2 id="dialog-judul" class="text-xl font-bold">{{ $judul }}</h2>
        <div class="text-sm">{{ $slot }}</div>
        <div class="flex flex-wrap justify-end gap-3">
            <button type="button" wire:click="{{ $batal }}"
                class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2.5 text-sm font-semibold transition hover:bg-cream">Batal</button>
            <button type="button" wire:click="{{ $aksi }}" wire:loading.attr="disabled"
                class="rounded-ta border-[1.5px] border-ink bg-coral px-4 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5 disabled:opacity-60">{{ $tombol }}</button>
        </div>
    </div>
</div>
