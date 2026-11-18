@props(['value', 'warna' => 'bg-paper'])

{{-- Pilihan tunggal berbentuk chip atau kartu (radio yang tersambung ke wire:model). Terpilih = latar gelap. --}}
@php $model = $attributes->whereStartsWith('wire:model'); @endphp
<label class="block cursor-pointer">
    <input type="radio" name="{{ $model->first() }}" value="{{ $value }}" {{ $model }} class="peer sr-only">
    <span class="flex h-full items-center gap-3 rounded-ta border-[1.5px] border-ink px-4 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5 peer-checked:bg-ink peer-checked:text-paper peer-focus-visible:outline-3 peer-focus-visible:outline-yellow {{ $warna }}">
        {{ $slot }}
    </span>
</label>
