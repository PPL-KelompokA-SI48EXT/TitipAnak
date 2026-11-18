@php
    // Warna status: Draft netral, lalu makin ke kanan makin "selesai".
    $warnaStatus = [
        'draft' => 'bg-cream',
        'menunggu_verifikasi' => 'bg-blue',
        'disetujui' => 'bg-mint',
        'ditolak' => 'bg-coral',
    ];
@endphp

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
            <span aria-hidden="true">✅</span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    @forelse ($pendaftaran as $p)
        <article class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-paper p-5 sm:flex sm:items-center sm:justify-between">
            <div class="grid gap-0.5">
                <h2 class="text-lg font-bold">{{ $p->paketLayanan->nama }}</h2>
                <p class="text-sm text-muted">
                    Rp {{ number_format($p->paketLayanan->harga, 0, ',', '.') }} · Dibuat {{ $p->created_at->format('d M Y') }}
                </p>
            </div>

            <span class="w-fit rounded-full border-[1.5px] border-ink px-3 py-1 text-sm font-semibold {{ $warnaStatus[$p->status] ?? 'bg-paper' }}">
                {{ $p->labelStatus() }}
            </span>
        </article>
    @empty
        <div class="grid gap-1 rounded-ta border-[1.5px] border-dashed border-ink bg-cream p-8 text-center">
            <p class="font-semibold">Belum ada pendaftaran.</p>
            <p class="text-sm text-muted">Mulai dengan memilih paket layanan.</p>
        </div>
    @endforelse
</div>
