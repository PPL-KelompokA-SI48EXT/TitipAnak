@php
    // Alert per status, mengikuti komponen "Banner & alert" di frontend guideline.
    $alert = [
        'menunggu_verifikasi' => ['bg-yellow', '⏳', 'Menunggu.', 'Pendaftaran ini sudah lengkap dan menunggu diperiksa Admin.'],
        'disetujui' => ['bg-mint', '✅', 'Disetujui.', 'Pendaftaran ini sudah disetujui.'],
        'ditolak' => ['bg-coral', '⚠️', 'Ditolak.', 'Pendaftaran ini tidak disetujui.'],
    ][$pendaftaran->status] ?? null;

    $tanggal = fn ($waktu) => $waktu?->format('d M Y, H:i') ?? '—';
    $isi = fn ($nilai) => filled($nilai) ? $nilai : '—';

    $usia = null;
    if ($anak?->tanggal_lahir) {
        $bulan = (int) floor($anak->tanggal_lahir->diffInMonths(now()));
        $usia = $bulan >= 12
            ? intdiv($bulan, 12).' tahun'.($bulan % 12 ? ' '.($bulan % 12).' bulan' : '')
            : $bulan.' bulan';
    }

    $kartu = 'grid content-start gap-4 rounded-ta border-[1.5px] border-ink bg-paper p-6';
    $kosong = 'rounded-ta border-[1.5px] border-dashed border-ink bg-cream px-4 py-6 text-center text-sm text-muted';
@endphp

<div class="grid gap-6">
    <div class="grid gap-3">
        <a href="{{ route('admin.pendaftaran.index') }}"
            class="w-fit text-sm font-semibold text-ink underline-offset-4 hover:underline">
            ← Kembali ke daftar pendaftaran
        </a>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold leading-tight">Periksa Pendaftaran</h1>
                <p class="text-muted">
                    {{ $anak?->nama_lengkap ?? 'Anak belum diisi' }} · diajukan oleh {{ $pendaftaran->orangTua?->nama ?? '—' }}
                </p>
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

    <div class="grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="pendaftaran-heading" class="{{ $kartu }}">
            <h2 id="pendaftaran-heading" class="text-xl font-bold">Pendaftaran</h2>
            <dl class="grid items-start gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div class="grid gap-0.5">
                    <dt class="text-muted">Paket layanan</dt>
                    <dd class="font-semibold">{{ $pendaftaran->paketLayanan?->nama ?? '—' }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Tarif</dt>
                    <dd class="font-semibold">
                        {{ $pendaftaran->paketLayanan ? 'Rp '.number_format($pendaftaran->paketLayanan->harga, 0, ',', '.') : '—' }}
                    </dd>
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
                <div class="grid gap-0.5">
                    <dt class="text-muted">Diverifikasi oleh</dt>
                    <dd class="font-semibold">{{ $isi($diverifikasiOleh) }}</dd>
                </div>
            </dl>
        </section>

        <section aria-labelledby="orang-tua-heading" class="{{ $kartu }}">
            <h2 id="orang-tua-heading" class="text-xl font-bold">Orang tua</h2>
            <dl class="grid items-start gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div class="grid gap-0.5">
                    <dt class="text-muted">Nama</dt>
                    <dd class="font-semibold">{{ $isi($pendaftaran->orangTua?->nama) }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">No. HP</dt>
                    <dd class="font-semibold">{{ $isi($profilOrangTua?->no_hp) }}</dd>
                </div>
                <div class="grid gap-0.5 sm:col-span-2">
                    <dt class="text-muted">Email</dt>
                    <dd class="break-words font-semibold">{{ $isi($pendaftaran->orangTua?->email) }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Pekerjaan</dt>
                    <dd class="font-semibold">{{ $isi($profilOrangTua?->pekerjaan) }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Kontak darurat</dt>
                    <dd class="font-semibold">{{ $isi($profilOrangTua?->kontak_darurat) }}</dd>
                </div>
                <div class="grid gap-0.5 sm:col-span-2">
                    <dt class="text-muted">Alamat</dt>
                    <dd class="font-semibold">{{ $isi($profilOrangTua?->alamat) }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <section aria-labelledby="anak-heading" class="{{ $kartu }}">
        <h2 id="anak-heading" class="text-xl font-bold">Data anak</h2>
        @if ($anak)
            <dl class="grid items-start gap-x-6 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="grid gap-0.5">
                    <dt class="text-muted">Nama lengkap</dt>
                    <dd class="font-semibold">{{ $anak->nama_lengkap }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Tanggal lahir</dt>
                    <dd class="font-semibold">
                        {{ $anak->tanggal_lahir?->format('d M Y') ?? '—' }}
                        @if ($usia) <span class="font-normal text-muted">({{ $usia }})</span> @endif
                    </dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Jenis kelamin</dt>
                    <dd class="font-semibold">{{ ['L' => 'Laki-laki', 'P' => 'Perempuan'][$anak->jenis_kelamin] ?? '—' }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Kelompok usia</dt>
                    <dd class="font-semibold">{{ $anak->kelompokUsia?->nama ?? 'Belum ditetapkan' }}</dd>
                </div>
            </dl>
        @else
            <p class="{{ $kosong }}">Data anak belum diisi.</p>
        @endif
    </section>

    <section aria-labelledby="kesehatan-heading" class="{{ $kartu }}">
        <h2 id="kesehatan-heading" class="text-xl font-bold">Catatan kesehatan</h2>
        @if ($catatanKesehatan)
            <dl class="grid items-start gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div class="grid gap-0.5">
                    <dt class="text-muted">Riwayat medis</dt>
                    <dd class="whitespace-pre-line font-semibold">{{ $isi($catatanKesehatan->riwayat_medis) }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Alergi</dt>
                    <dd class="whitespace-pre-line font-semibold">{{ $isi($catatanKesehatan->alergi) }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Pantangan makanan</dt>
                    <dd class="whitespace-pre-line font-semibold">{{ $isi($catatanKesehatan->pantangan_makanan) }}</dd>
                </div>
                <div class="grid gap-0.5">
                    <dt class="text-muted">Catatan lain</dt>
                    <dd class="whitespace-pre-line font-semibold">{{ $isi($catatanKesehatan->catatan) }}</dd>
                </div>
            </dl>
        @else
            <p class="{{ $kosong }}">Catatan kesehatan belum diisi.</p>
        @endif
    </section>

    <section aria-labelledby="penjemput-heading" class="{{ $kartu }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 id="penjemput-heading" class="text-xl font-bold">Penjemput terdaftar</h2>
            <span class="inline-flex rounded-full border border-ink bg-paper px-3 py-1 text-xs font-semibold">{{ $penjemput->count() }} orang</span>
        </div>
        @if ($penjemput->isNotEmpty())
            <div class="relative overflow-x-auto rounded-ta border-[1.5px] border-ink">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar penjemput terdaftar</caption>
                    <thead>
                        <tr class="bg-cream text-left">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-bold">Nama</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-bold">Hubungan</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-bold">No. HP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($penjemput as $orang)
                            <tr wire:key="penjemput-{{ $orang->id }}" class="border-b border-ink last:border-b-0">
                                <td class="px-4 py-3 font-semibold">{{ $orang->nama_lengkap }}</td>
                                <td class="px-4 py-3">{{ $orang->hubungan }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $orang->no_hp }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="{{ $kosong }}">Belum ada penjemput terdaftar.</p>
        @endif
    </section>
</div>
