@php
    $input = 'rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $adaFilter = $statusAktif !== '' || trim($q) !== '';

    $tab = ['' => 'Semua'] + collect(\App\Livewire\Admin\PendaftaranIndex::STATUS_MASUK)
        ->mapWithKeys(fn ($s) => [$s => \App\Models\Pendaftaran::STATUS[$s]])
        ->all();

    // Usia anak dalam tahun dan bulan, mis. "2 th 3 bln".
    $usia = function ($tanggalLahir) {
        if (! $tanggalLahir) {
            return null;
        }
        $bulan = (int) floor($tanggalLahir->diffInMonths(now()));

        return $bulan >= 12
            ? intdiv($bulan, 12).' th'.($bulan % 12 ? ' '.($bulan % 12).' bln' : '')
            : $bulan.' bln';
    };
@endphp

<div class="grid gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold leading-tight">Pendaftaran</h1>
            <p class="text-muted">Periksa pendaftaran yang diajukan orang tua satu per satu.</p>
        </div>
        <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-sm font-semibold">
            {{ $jumlah['menunggu_verifikasi'] ?? 0 }} menunggu verifikasi
        </span>
    </div>

    @if (session('sukses'))
        <div role="status" class="flex gap-3 rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm">
            <span aria-hidden="true"><x-ikon e="✅" /></span>
            <span><b>Berhasil.</b> {{ session('sukses') }}</span>
        </div>
    @endif

    {{-- Tab status --}}
    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter status pendaftaran">
        @foreach ($tab as $kode => $label)
            <button type="button" wire:click="pilihStatus('{{ $kode }}')" aria-pressed="{{ $statusAktif === $kode ? 'true' : 'false' }}"
                wire:key="tab-{{ $kode ?: 'semua' }}"
                class="inline-flex min-h-11 items-center gap-2 rounded-full border-[1.5px] border-ink px-4 py-2 text-sm font-semibold transition {{ $statusAktif === $kode ? 'bg-ink text-paper' : 'bg-paper hover:bg-cream' }}">
                {{ $label }}
                <span class="rounded-full border border-current px-2 text-xs">{{ $kode === '' ? $jumlahSemua : ($jumlah[$kode] ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    {{-- Pencarian --}}
    <div class="flex flex-wrap items-center gap-3">
        <label class="relative min-w-56 flex-1">
            <span class="sr-only">Cari nama anak atau orang tua</span>
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true"><x-ikon e="🔍" /></span>
            <input type="search" wire:model.live.debounce.300ms="q" placeholder="Cari nama anak atau orang tua…" autocomplete="off"
                class="{{ $input }} w-full pl-10">
        </label>

        @if ($adaFilter)
            <button type="button" wire:click="resetFilter"
                class="min-h-11 rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2.5 text-sm font-semibold transition hover:bg-cream">Reset filter</button>
        @endif
    </div>

    @if ($pendaftaran->isEmpty())
        <div class="grid justify-items-center gap-1 rounded-ta border-[1.5px] border-dashed border-ink bg-cream p-8 text-center">
            <span class="text-3xl" aria-hidden="true"><x-ikon :e="$jumlahSemua === 0 ? '📭' : '🔎'" /></span>
            <p class="text-xl font-bold">{{ $jumlahSemua === 0 ? 'Belum ada pendaftaran masuk' : 'Tidak ada hasil' }}</p>
            <p class="text-sm text-muted">
                {{ $jumlahSemua === 0
                    ? 'Pendaftaran muncul di sini setelah orang tua melengkapi data anak, catatan kesehatan, dan penjemput.'
                    : 'Coba kata kunci lain atau reset filter.' }}
            </p>
            @if ($adaFilter && $jumlahSemua > 0)
                <button type="button" wire:click="resetFilter"
                    class="mt-3 min-h-11 rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2.5 text-sm font-semibold transition hover:bg-cream">Reset filter</button>
            @endif
        </div>
    @else
        <div class="relative overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
            <table class="w-full border-collapse text-sm">
                <caption class="sr-only">Daftar pendaftaran yang masuk</caption>
                <thead>
                    <tr class="bg-yellow text-left">
                        <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Anak</th>
                        <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Orang tua</th>
                        <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Paket</th>
                        <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Diajukan</th>
                        <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-bold">Status</th>
                        <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendaftaran as $p)
                        <tr wire:key="pendaftaran-{{ $p->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                            <td class="min-w-44 px-4 py-3">
                                <p class="font-semibold">{{ $p->anak?->nama_lengkap ?? '—' }}</p>
                                @if ($p->anak)
                                    <p class="whitespace-nowrap text-xs text-muted">
                                        {{ $p->anak->jenis_kelamin === 'P' ? 'Perempuan' : 'Laki-laki' }}
                                        @if ($usia($p->anak->tanggal_lahir)) · {{ $usia($p->anak->tanggal_lahir) }} @endif
                                    </p>
                                @endif
                            </td>
                            <td class="min-w-44 px-4 py-3">
                                <p class="font-semibold">{{ $p->orangTua?->nama ?? '—' }}</p>
                                <p class="text-xs text-muted">{{ $p->orangTua?->email }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $p->paketLayanan?->nama ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $p->diajukan_pada?->format('d M Y, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-pendaftaran.status-badge :status="$p->status" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.pendaftaran.detail', $p) }}"
                                    class="inline-flex min-h-11 items-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-xs font-semibold transition hover:-translate-y-0.5 hover:bg-cream"
                                    aria-label="Periksa pendaftaran {{ $p->anak?->nama_lengkap ?? $p->orangTua?->nama }}">
                                    Periksa
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
