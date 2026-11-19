@php
    $tab = ['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan', 'semua' => 'Semua'];
    $badge = [
        'menunggu' => 'bg-yellow text-ink',
        'disetujui' => 'bg-ink text-paper',
        'ditolak' => 'bg-paper text-ink border-dashed',
        'dibatalkan' => 'bg-cream text-muted',
    ];
    $tombol = 'inline-flex min-h-8 items-center justify-center rounded-ta border-[1.5px] border-ink px-2.5 py-1 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50';
@endphp

<div class="grid gap-8">
    <header>
        <h1 class="text-3xl font-bold tracking-tight text-ink">Izin Penjemputan</h1>
        <p class="mt-1 max-w-2xl text-sm text-muted">Orang tua mengajukan izin bila anak dijemput orang di luar daftar penjemput terdaftar. Cocokkan KTP penjemput lalu setujui atau tolak.</p>
    </header>

    @if (session('sukses'))
        <div role="status" class="rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-3 text-sm font-semibold text-ink">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div role="alert" class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-3 text-sm font-semibold text-ink">{{ session('galat') }}</div>
    @endif

    <section aria-labelledby="izin-masuk-heading">
        <h2 id="izin-masuk-heading" class="sr-only">Izin penjemputan yang masuk</h2>

        <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filter status">
            @foreach ($tab as $nilai => $label)
                <button
                    type="button"
                    wire:click="$set('filterStatus', '{{ $nilai }}')"
                    aria-pressed="{{ $filterStatus === $nilai ? 'true' : 'false' }}"
                    class="inline-flex min-h-9 items-center gap-2 rounded-full border-[1.5px] border-ink px-3.5 py-1 text-xs font-semibold text-ink {{ $filterStatus === $nilai ? 'bg-yellow' : 'bg-paper hover:bg-cream' }}"
                >
                    {{ $label }}
                    @if ($nilai !== 'semua')
                        <span class="text-muted">{{ $jumlah[$nilai] ?? 0 }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        @if ($izin->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">Tidak ada izin penjemputan pada filter ini.</div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar izin penjemputan</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Tanggal jemput</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Anak</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Penjemput</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Alasan</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Status</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($izin as $item)
                            <tr wire:key="izin-{{ $item->id }}" class="border-b border-ink align-top last:border-b-0 even:bg-cream">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">
                                    {{ $item->tanggal_jemput->translatedFormat('d M Y') }}
                                    @if ($item->tanggal_jemput->isToday())
                                        <span class="ml-1 rounded-full border border-ink bg-yellow px-2 py-0.5 text-[10px] font-semibold">Hari ini</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink">{{ $item->anak->nama_lengkap }}<span class="block text-xs text-muted">Diajukan {{ $item->diajukanOleh?->nama ?? '—' }}</span></td>
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->nama_penjemput }}</td>
                                <td class="max-w-xs px-4 py-3 text-muted">{{ $item->alasan }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full border border-ink px-2.5 py-0.5 text-xs font-semibold capitalize {{ $badge[$item->status] ?? 'bg-cream text-ink' }}">{{ $item->status }}</span>
                                    @if ($item->divalidasiOleh)
                                        <span class="mt-1 block text-xs text-muted">oleh {{ $item->divalidasiOleh->nama }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" wire:click="lihatFoto({{ $item->id }})" class="{{ $tombol }} bg-yellow">Lihat KTP</button>
                                        @if ($item->status === 'menunggu')
                                            <button type="button" wire:click="setujui({{ $item->id }})" wire:loading.attr="disabled" class="{{ $tombol }} bg-ink !text-paper">Setujui</button>
                                            <button type="button" wire:click="tolak({{ $item->id }})" wire:loading.attr="disabled" class="{{ $tombol }} bg-paper">Tolak</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($viewingFotoId !== null)
        <div class="fixed inset-0 z-50 flex min-h-full items-center justify-center overflow-y-auto bg-ink/60 p-4" wire:key="foto-izin-{{ $viewingFotoId }}" wire:keydown.escape="tutupFoto">
            <dialog open role="dialog" aria-modal="true" class="relative m-0 mx-auto my-auto w-full max-w-md rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
                <div class="grid gap-4 p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-ink/20 pb-3">
                        <div>
                            <h2 class="text-lg font-bold text-ink">Foto KTP Penjemput</h2>
                            <p class="text-xs text-muted">Cocokkan dengan orang yang datang menjemput.</p>
                        </div>
                        <button type="button" wire:click="tutupFoto" class="rounded-ta border border-ink p-1 text-ink hover:bg-cream" aria-label="Tutup dialog">✕</button>
                    </div>
                    @if ($viewingIzin)
                        <div class="rounded-ta border border-ink bg-cream p-3 text-xs">
                            <span class="text-muted">Nama:</span> <span class="font-semibold text-ink">{{ $viewingIzin->nama_penjemput }}</span><br>
                            <span class="text-muted">Menjemput:</span> <span class="font-semibold text-ink">{{ $viewingIzin->anak->nama_lengkap }}</span>
                        </div>
                    @endif
                    <div class="text-center">
                        @if ($previewFotoUrl)
                            <img src="{{ $previewFotoUrl }}" alt="Foto KTP penjemput" class="mx-auto max-h-80 w-auto rounded border border-ink object-contain">
                        @else
                            <div class="rounded border border-dashed border-ink p-6 text-xs text-muted">Berkas foto tidak ditemukan di penyimpanan.</div>
                        @endif
                    </div>
                </div>
            </dialog>
        </div>
    @endif
</div>
