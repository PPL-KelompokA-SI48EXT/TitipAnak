{{--
    PBI-11b: tahapan pendaftaran (Draft -> Menunggu Verifikasi -> Disetujui).
    Mobile: daftar vertikal. Layar >= sm: berderet horizontal dengan garis penghubung.
--}}
@props(['pendaftaran'])

@php
    $keterangan = [
        'draft' => 'Pendaftaran dibuat.',
        'menunggu_verifikasi' => 'Data lengkap dan diajukan ke Admin.',
        'disetujui' => 'Pendaftaran diterima Admin.',
        'ditolak' => 'Pendaftaran tidak disetujui Admin.',
    ];
    $warnaSelesai = ['ditolak' => 'bg-coral', 'disetujui' => 'bg-mint'];
    $teksKeadaan = ['selesai' => 'selesai', 'aktif' => 'tahap saat ini', 'belum' => 'belum dimulai'];
@endphp

<ol {{ $attributes->class(['grid gap-5 sm:flex sm:gap-0']) }}>
    @foreach ($pendaftaran->tahapan() as $i => $tahap)
        <li @class(['relative flex gap-3 sm:flex-1 sm:flex-col sm:items-center sm:text-center'])
            @if ($tahap['keadaan'] === 'aktif') aria-current="step" @endif>
            @if ($i > 0)
                <span aria-hidden="true" @class([
                    'absolute top-5 hidden border-t-[1.5px] border-ink left-[calc(-50%+1.75rem)] right-[calc(50%+1.75rem)] sm:block',
                    'border-dashed' => $tahap['keadaan'] === 'belum',
                ])></span>
            @endif

            <span aria-hidden="true" @class([
                'grid size-10 flex-none place-items-center rounded-full border-[1.5px] text-sm font-bold',
                'border-ink '.($warnaSelesai[$tahap['status']] ?? 'bg-mint') => $tahap['keadaan'] === 'selesai',
                'border-ink bg-yellow' => $tahap['keadaan'] === 'aktif',
                'border-dashed border-ink bg-paper text-muted' => $tahap['keadaan'] === 'belum',
            ])>
                @if ($tahap['keadaan'] === 'selesai')
                    {{ $tahap['status'] === 'ditolak' ? '✕' : '✓' }}
                @else
                    {{ $i + 1 }}
                @endif
            </span>

            <div class="grid gap-0.5 sm:px-2">
                <p @class(['font-semibold', 'text-muted' => $tahap['keadaan'] === 'belum'])>
                    {{ $tahap['label'] }}
                    <span class="sr-only">({{ $teksKeadaan[$tahap['keadaan']] }})</span>
                </p>
                <p class="text-xs text-muted">
                    {{ $tahap['waktu']?->translatedFormat('d M Y, H:i') ?? 'Belum' }}
                </p>
                @if ($tahap['keadaan'] !== 'belum')
                    <p class="text-sm">{{ $keterangan[$tahap['status']] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
