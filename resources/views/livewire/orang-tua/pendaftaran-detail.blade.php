@php
    // Alert per status, mengikuti komponen "Banner & alert" di frontend guideline.
    $alert = [
        'draft' => ['bg-blue', 'ℹ️', 'Masih Draft.', 'Lengkapi data anak, catatan kesehatan, dan penjemput terdaftar agar pendaftaran bisa diperiksa Admin.'],
        'menunggu_verifikasi' => ['bg-yellow', '⏳', 'Menunggu.', 'Pendaftaran Anda sedang diverifikasi Admin.'],
        'disetujui' => ['bg-mint', '✅', 'Disetujui.', 'Pendaftaran Anda sudah diterima Admin.'],
        'ditolak' => ['bg-coral', '⚠️', 'Ditolak.', 'Pendaftaran Anda tidak disetujui Admin.'],
    ][$pendaftaran->status] ?? null;

    $tanggal = fn ($waktu) => $waktu?->translatedFormat('d M Y, H:i') ?? '—';
@endphp

<div class="grid gap-6">
    <div class="grid gap-3">
        <a href="{{ route('orang-tua.pendaftaran.index') }}"
            class="w-fit text-sm font-semibold text-ink underline-offset-4 hover:underline">
            ← Kembali ke Pendaftaran Saya
        </a>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold leading-tight">Status Pendaftaran</h1>
                <p class="text-muted">Lihat sudah sampai tahap mana pendaftaran Anda.</p>
            </div>
            <x-pendaftaran.status-badge :status="$pendaftaran->status" class="text-sm" />
        </div>
    </div>

    @if ($alert)
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink px-4 py-3 text-sm {{ $alert[0] }}">
            <span aria-hidden="true">{{ $alert[1] }}</span>
            <div class="grid gap-1">
                <span><b>{{ $alert[2] }}</b> {{ $alert[3] }}</span>
                @if ($pendaftaran->status === 'ditolak' && $pendaftaran->catatan_penolakan)
                    <span><b>Alasan:</b> {{ $pendaftaran->catatan_penolakan }}</span>
                @endif
            </div>
        </div>
    @endif

    <section aria-labelledby="tahapan-heading" class="grid gap-5 rounded-ta border-[1.5px] border-ink bg-paper p-6">
        <h2 id="tahapan-heading" class="text-xl font-bold">Tahapan</h2>
        <x-pendaftaran.tahapan :pendaftaran="$pendaftaran" />
    </section>

    <section aria-labelledby="rincian-heading" class="grid gap-4 rounded-ta border-[1.5px] border-ink bg-paper p-6">
        <h2 id="rincian-heading" class="text-xl font-bold">Rincian</h2>

        <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
            <div class="grid gap-0.5">
                <dt class="text-muted">Paket layanan</dt>
                <dd class="font-semibold">{{ $pendaftaran->paketLayanan->nama }} · Rp {{ number_format($pendaftaran->paketLayanan->harga, 0, ',', '.') }}</dd>
            </div>
            <div class="grid gap-0.5">
                <dt class="text-muted">Nama anak</dt>
                <dd class="font-semibold">{{ $pendaftaran->anak?->nama_lengkap ?? 'Belum diisi' }}</dd>
            </div>
            <div class="grid gap-0.5">
                <dt class="text-muted">Dibuat</dt>
                <dd class="font-semibold">{{ $tanggal($pendaftaran->created_at) }}</dd>
            </div>
            <div class="grid gap-0.5">
                <dt class="text-muted">Diajukan</dt>
                <dd class="font-semibold">{{ $tanggal($pendaftaran->diajukan_pada) }}</dd>
            </div>
            <div class="grid gap-0.5">
                <dt class="text-muted">Diverifikasi</dt>
                <dd class="font-semibold">{{ $tanggal($pendaftaran->diverifikasi_pada) }}</dd>
            </div>
        </dl>
    </section>

    @if ($pendaftaran->bisaDihapus())
        {{-- PBI-11c: orang tua boleh membatalkan pendaftaran selama masih Draft. --}}
        <section aria-labelledby="batal-heading"
            class="grid gap-3 rounded-ta border-[1.5px] border-ink bg-paper p-6 sm:flex sm:items-center sm:justify-between">
            <div class="grid gap-0.5">
                <h2 id="batal-heading" class="text-xl font-bold">Batal mendaftar?</h2>
                <p class="text-sm text-muted">Pendaftaran yang masih Draft bisa dihapus. Setelah diajukan, pendaftaran tidak bisa dihapus lagi.</p>
            </div>
            <button type="button" wire:click="konfirmasiHapus"
                class="inline-flex min-h-11 w-fit shrink-0 items-center whitespace-nowrap rounded-ta border-[1.5px] border-ink bg-coral px-4 py-2 text-sm font-semibold transition hover:-translate-y-0.5">
                Hapus pendaftaran
            </button>
        </section>
    @endif

    @if ($konfirmasiHapusTerbuka && $pendaftaran->bisaDihapus())
        <x-konfirmasi-hapus judul="Hapus pendaftaran?" tombol="Ya, hapus">
            <x-pendaftaran.pesan-hapus :pendaftaran="$pendaftaran" />
        </x-konfirmasi-hapus>
    @endif
</div>
