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
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Kontak</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Identitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penjemput as $item)
                            <tr wire:key="penjemput-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->anak->nama_lengkap }}</td>
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->nama_lengkap }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->hubungan }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->no_hp }}</td>
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->path_foto_identitas ? 'Tersimpan' : 'Tidak tersedia' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
