<div class="grid gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold leading-tight">Pendaftaran Saya</h1>
            <p class="text-muted">Pantau status pendaftaran layanan penitipan anak Anda.</p>
        </div>
        <a href="{{ route('orang-tua.pendaftaran.buat') }}"
            class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
            Buat pendaftaran
        </a>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="✅" /></span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    @forelse ($pendaftaran as $p)
        @php
            // PBI-11b: posisi tahap (Ditolak dihitung di tahap terakhir).
            $tahapKe = $p->status === 'ditolak' ? 3 : array_search($p->status, \App\Models\Pendaftaran::TAHAPAN, true) + 1;
        @endphp
        <article wire:key="pendaftaran-{{ $p->id }}"
            class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-paper p-5 sm:flex sm:items-center sm:justify-between">
            <div class="grid gap-0.5">
                <h2 class="text-lg font-bold">{{ $p->paketLayanan->nama }}</h2>
                <p class="text-sm text-muted">
                    @if ($p->anak) {{ $p->anak->nama_lengkap }} · @endif
                    Rp {{ number_format($p->paketLayanan->harga, 0, ',', '.') }} · Dibuat {{ $p->created_at->format('d M Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 sm:shrink-0">
                <div class="grid gap-1 sm:justify-items-end">
                    <x-pendaftaran.status-badge :status="$p->status" class="text-sm" />
                    <span class="text-xs text-muted">Tahap {{ $tahapKe }} dari {{ count(\App\Models\Pendaftaran::TAHAPAN) }}</span>
                </div>
                <a href="{{ route('orang-tua.pendaftaran.detail', $p) }}"
                    class="inline-flex min-h-11 items-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold transition hover:bg-cream"
                    aria-label="Lihat status pendaftaran {{ $p->paketLayanan->nama }}">
                    Lihat status
                </a>
                @if ($p->bisaDihapus())
                    {{-- PBI-11c: hanya pendaftaran Draft yang boleh dihapus. Coral = aksi merusak. --}}
                    <button type="button" wire:click="konfirmasiHapus({{ $p->id }})"
                        class="inline-flex min-h-11 items-center rounded-ta border-[1.5px] border-ink bg-coral px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5"
                        aria-label="Hapus pendaftaran {{ $p->paketLayanan->nama }}">
                        Hapus
                    </button>
                @endif
            </div>
        </article>
    @empty
        <div class="grid gap-1 rounded-ta border-[1.5px] border-dashed border-ink bg-cream p-8 text-center">
            <p class="font-semibold">Belum ada pendaftaran.</p>
            <p class="text-sm text-muted">Mulai dengan memilih paket layanan.</p>
        </div>
    @endforelse

    @if ($hapusPendaftaran)
        <x-konfirmasi-hapus judul="Hapus pendaftaran?" tombol="Ya, hapus">
            <x-pendaftaran.pesan-hapus :pendaftaran="$hapusPendaftaran" />
        </x-konfirmasi-hapus>
    @endif
</div>
