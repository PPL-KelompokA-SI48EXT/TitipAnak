@props(['nomor', 'judul', 'hint' => null, 'warna' => 'bg-yellow'])

{{-- Satu langkah pada form: kepala berwarna dengan nomor, lalu isinya. --}}
<section {{ $attributes->class(['overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper']) }} aria-label="{{ $judul }}">
    <div class="flex items-center gap-3 border-b-[1.5px] border-ink px-5 py-3 {{ $warna }}">
        <span class="grid size-8 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-paper font-bold" aria-hidden="true">{{ $nomor }}</span>
        <div class="min-w-0">
            <h2 class="font-bold leading-tight">{{ $judul }}</h2>
            @if ($hint)
                <p class="text-xs">{{ $hint }}</p>
            @endif
        </div>
    </div>
    <div class="grid gap-4 p-5">{{ $slot }}</div>
</section>
