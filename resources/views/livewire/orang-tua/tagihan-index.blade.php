<div class="grid gap-8">
    <header>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Rincian Tagihan</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted">Lihat rincian dan status tagihan layanan penitipan anak Anda.</p>
        </div>
    </header>

    <section aria-labelledby="daftar-tagihan-heading">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="daftar-tagihan-heading" class="text-xl font-bold text-ink">Daftar Tagihan</h2>
                <p class="mt-1 text-sm text-muted">Tagihan yang diterbitkan untuk pendaftaran Anda.</p>
            </div>
            <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $tagihan->count() }} tagihan</span>
        </div>

        @if ($tagihan->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                Belum ada tagihan.
            </div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar rincian tagihan Anda</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nomor tagihan</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Paket layanan</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Jumlah</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Diterbitkan</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Jatuh tempo</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tagihan as $item)
                            <tr wire:key="tagihan-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->nomor_tagihan }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->nama_paket }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">Rp {{ number_format((float) $item->jumlah, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ \Carbon\Carbon::parse($item->diterbitkan_pada)->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $item->jatuh_tempo ? \Carbon\Carbon::parse($item->jatuh_tempo)->format('d/m/Y') : '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($item->status === 'lunas')
                                        <span class="inline-flex rounded-full border border-ink bg-green-200 px-2.5 py-1 text-xs font-semibold text-ink">Lunas</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-ink bg-coral/30 px-2.5 py-1 text-xs font-semibold text-ink">Belum Lunas</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
