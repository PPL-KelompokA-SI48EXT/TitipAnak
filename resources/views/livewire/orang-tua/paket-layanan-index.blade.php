<div class="grid gap-8">
    <header>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Paket Layanan</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted">Pilih paket layanan yang sesuai untuk anak Anda.</p>
        </div>
    </header>

    <section aria-labelledby="daftar-paket-heading">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="daftar-paket-heading" class="text-xl font-bold text-ink">Daftar paket</h2>
                <p class="mt-1 text-sm text-muted">Paket yang tersedia untuk pendaftaran.</p>
            </div>
            <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $paketLayanan->count() }} paket</span>
        </div>

        @if ($paketLayanan->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                Belum ada paket layanan yang tersedia.
            </div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar paket layanan untuk orang tua</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nama paket</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Deskripsi</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Tarif</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paketLayanan as $paket)
                            <tr wire:key="paket-{{ $paket->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="px-4 py-3 font-semibold text-ink">{{ $paket->nama }}</td>
                                <td class="max-w-sm px-4 py-3 text-muted">{{ $paket->deskripsi ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">Rp {{ number_format((float) $paket->harga, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
