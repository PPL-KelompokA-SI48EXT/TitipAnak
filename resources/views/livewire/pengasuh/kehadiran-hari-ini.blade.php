@php
    $label = ['belum' => 'Belum datang', 'hadir' => 'Hadir', 'pulang' => 'Sudah pulang'];
    $warna = ['belum' => 'bg-cream', 'hadir' => 'bg-mint', 'pulang' => 'bg-blue'];
@endphp

<div class="grid gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold leading-tight">Kehadiran Hari Ini</h1>
            <p class="text-muted">Daftar kehadiran anak di kelompok Anda · {{ $hariIni->day }}/{{ $hariIni->month }}/{{ $hariIni->year }}</p>
        </div>
        <a href="{{ route('pengasuh.presensi') }}"
            class="inline-flex items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
            Catat presensi
        </a>
    </div>

    @if ($anak->isEmpty())
        <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <div class="text-xl font-bold">Tidak ada anak untuk hari ini</div>
            <p class="mt-1 text-sm text-muted">Anda tidak punya shift hari ini, atau kelompok usia Anda belum punya anak aktif.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-yellow text-left">
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Anak</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Kelompok usia</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Status</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Jam datang</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Kondisi</th>
                        <th class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Jam pulang</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($anak as $a)
                        @php $p = $a->presensi_hari_ini; @endphp
                        <tr wire:key="anak-{{ $a->id }}" class="border-b border-ink last:border-b-0 even:bg-cream/50">
                            <td class="px-4 py-3 font-semibold">{{ $a->nama_lengkap }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $a->kelompokUsia?->nama ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full border border-ink px-3 py-1 text-xs font-semibold {{ $warna[$a->status_hari_ini] }}">{{ $label[$a->status_hari_ini] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $p?->check_in_pada->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $p?->kondisi_check_in ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $p?->check_out_pada?->format('H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
