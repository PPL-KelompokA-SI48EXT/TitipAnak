@props(['tutup' => 'batal', 'lebar' => 'max-w-2xl', 'label' => 'Dialog'])

{{-- Pembungkus dialog (overlay). Isinya = slot, biasanya sebuah form berbentuk kartu. Esc atau klik di luar menutup. --}}
<div class="fixed inset-0 z-50 flex overflow-y-auto bg-ink/50 p-4" role="dialog" aria-modal="true" aria-label="{{ $label }}"
    wire:keydown.escape.window="{{ $tutup }}" wire:click.self="{{ $tutup }}">
    <div class="m-auto w-full {{ $lebar }}">{{ $slot }}</div>
</div>
