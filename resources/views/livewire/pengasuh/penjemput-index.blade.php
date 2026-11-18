<div class="grid gap-8">
    <header>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Daftar Penjemput Terdaftar</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted">Gunakan daftar ini untuk mencocokkan penjemput saat anak pulang.</p>
        </div>
    </header>

    <section aria-labelledby="daftar-penjemput-heading">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="daftar-penjemput-heading" class="text-xl font-bold text-ink">Penjemput Aktif</h2>
                <p class="mt-1 text-sm text-muted">Daftar penjemput yang masih berwenang menjemput anak.</p>
            </div>
            <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $penjemput->count() }} orang</span>
        </div>

        @if ($penjemput->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                Belum ada penjemput aktif yang terdaftar.
            </div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar penjemput aktif yang boleh menjemput anak</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Anak</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nama penjemput</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Hubungan</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Kontak</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Identitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penjemput as $item)
                            <tr wire:key="penjemput-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->anak->nama_lengkap }}</td>
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->nama_lengkap }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->hubungan }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $item->no_hp }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($item->path_foto_identitas)
                                        <button
                                            type="button"
                                            wire:click="lihatFoto({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            class="inline-flex min-h-8 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-2.5 py-1 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Lihat foto
                                        </button>
                                    @else
                                        <span class="text-xs text-muted">Tidak tersedia</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Modal Pratinjau Foto Identitas (Pengasuh) --}}
    @if ($viewingFotoId !== null)
        <div
            x-data
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="lihat-foto-penjemput-pengasuh-{{ $viewingFotoId }}"
            wire:keydown.escape="tutupLihatFoto"
        >
            <dialog
                open
                role="dialog"
                aria-modal="true"
                class="relative m-0 mx-auto my-auto w-full max-w-md rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl"
            >
                <div class="grid gap-4 p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-ink/20 pb-3">
                        <div>
                            <h2 class="text-lg font-bold text-ink">Foto Identitas Penjemput</h2>
                            <p class="text-xs text-muted">Pencocokan identitas penjemput saat anak pulang.</p>
                        </div>
                        <button type="button" wire:click="tutupLihatFoto" class="rounded-ta border border-ink p-1 text-ink hover:bg-cream" aria-label="Tutup dialog">✕</button>
                    </div>

                    <div class="rounded-ta border border-ink bg-cream p-3 text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-muted">Nama:</span>
                                <p class="font-semibold text-ink">{{ $previewFotoNama }}</p>
                            </div>
                            <div>
                                <span class="text-muted">Hubungan:</span>
                                <p class="font-semibold text-ink">{{ $previewFotoHubungan }}</p>
                            </div>
                            <div class="col-span-2">
                                <span class="text-muted">Menjemput Anak:</span>
                                <p class="font-semibold text-ink">{{ $previewFotoAnak }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        @if ($previewFotoUrl)
                            <img src="{{ $previewFotoUrl }}" alt="Foto identitas {{ $previewFotoNama }}" class="mx-auto max-h-80 w-auto rounded border border-ink object-contain">
                        @else
                            <div class="rounded border border-dashed border-ink p-6 text-xs text-muted">Berkas foto tidak ditemukan di penyimpanan.</div>
                        @endif
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" wire:click="tutupLihatFoto" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-xs font-semibold text-ink hover:bg-cream">Tutup</button>
                    </div>
                </div>
            </dialog>
        </div>
    @endif
</div>
