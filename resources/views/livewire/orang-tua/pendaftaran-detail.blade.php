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
            <x-ikon :e="$alert[1]" />
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

    @if ($kelengkapan)
        {{-- PBI-11d: syarat agar Draft otomatis menjadi Menunggu Verifikasi. --}}
        @php
            $petunjuk = [
                'anak' => 'Nama, tanggal lahir, dan jenis kelamin anak.',
                'kesehatan' => 'Riwayat medis, alergi, dan pantangan makanan anak.',
                'penjemput' => 'Minimal satu orang yang boleh menjemput anak.',
            ];
            $jumlahLengkap = collect($kelengkapan)->where('lengkap', true)->count();
        @endphp
        <section aria-labelledby="kelengkapan-heading" class="grid gap-4 rounded-ta border-[1.5px] border-ink bg-paper p-6">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="grid gap-0.5">
                    <h2 id="kelengkapan-heading" class="text-xl font-bold">Kelengkapan data</h2>
                    <p class="text-sm text-muted">Setelah ketiganya lengkap, status otomatis berubah menjadi Menunggu Verifikasi dan pendaftaran diteruskan ke Admin.</p>
                </div>
                <span class="text-sm font-semibold">{{ $jumlahLengkap }} dari {{ count($kelengkapan) }} lengkap</span>
            </div>

            <ul class="grid gap-2">
                @foreach ($kelengkapan as $kunci => $syarat)
                    {{-- Mobile: ikon | teks | badge, tombol turun ke baris sendiri. Layar >= sm: satu baris. --}}
                    <li wire:key="kelengkapan-{{ $kunci }}"
                        class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-ta border border-ink bg-paper px-4 py-3 sm:flex">
                        <span aria-hidden="true" @class([
                            'grid size-8 flex-none place-items-center rounded-full border-[1.5px] border-ink text-sm font-bold',
                            'bg-mint' => $syarat['lengkap'],
                            'border-dashed bg-paper text-muted' => ! $syarat['lengkap'],
                        ])>{{ $syarat['lengkap'] ? '✓' : '!' }}</span>

                        <div class="grid min-w-0 flex-1 gap-0.5">
                            <p class="font-semibold">{{ $syarat['label'] }}</p>
                            <p class="text-xs text-muted">{{ $petunjuk[$kunci] }}</p>
                        </div>

                        @if (! $syarat['lengkap'] && $kunci === 'penjemput' && $pendaftaran->anak && auth()->user()->can('kelola penjemput'))
                            <a href="{{ route('orang-tua.penjemput.index') }}"
                                class="col-span-3 row-start-2 inline-flex min-h-11 w-fit items-center whitespace-nowrap rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold transition hover:bg-cream sm:shrink-0">
                                Tambah penjemput
                            </a>
                        @endif

                        <span @class([
                            'inline-flex w-fit flex-none items-center whitespace-nowrap rounded-full border border-ink px-3 py-1 text-xs font-semibold sm:order-last',
                            'bg-mint' => $syarat['lengkap'],
                            'bg-paper' => ! $syarat['lengkap'],
                        ])>{{ $syarat['lengkap'] ? 'Lengkap' : 'Belum diisi' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

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
