@php
    $badge = [
        'menunggu' => ['Menunggu validasi', 'bg-yellow text-ink'],
        'disetujui' => ['Disetujui', 'bg-ink text-paper'],
        'ditolak' => ['Ditolak', 'bg-paper text-ink border-dashed'],
        'dibatalkan' => ['Dibatalkan', 'bg-cream text-muted'],
    ];
    $input = 'w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow';
    $tombol = 'inline-flex min-h-8 items-center justify-center rounded-ta border-[1.5px] border-ink px-2.5 py-1 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50';
@endphp

<div class="grid gap-8">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Izin Penjemputan</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted">Ajukan izin bila anak dijemput orang yang tidak ada di daftar penjemput terdaftar. Pengasuh akan memvalidasinya saat anak diserahkan.</p>
        </div>
        @if ($anak->isNotEmpty())
            <button type="button" wire:click="bukaForm" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                + Ajukan Izin
            </button>
        @endif
    </header>

    @if (session('sukses'))
        <div role="status" class="rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-3 text-sm font-semibold text-ink">{{ session('sukses') }}</div>
    @endif
    @if (session('galat'))
        <div role="alert" class="rounded-ta border-[1.5px] border-ink bg-paper px-4 py-3 text-sm font-semibold text-ink">{{ session('galat') }}</div>
    @endif

    @if ($anak->isEmpty())
        <section class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center">
            <h2 class="text-xl font-bold text-ink">Belum ada anak aktif</h2>
            <p class="mt-2 text-sm text-muted">Izin penjemputan bisa diajukan setelah pendaftaran anak disetujui.</p>
        </section>
    @else
        <section aria-labelledby="daftar-izin-heading">
            <div class="mb-4 flex items-end justify-between gap-4">
                <h2 id="daftar-izin-heading" class="text-xl font-bold text-ink">Izin yang Saya Ajukan</h2>
                <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $izin->count() }} izin</span>
            </div>

            @if ($izin->isEmpty())
                <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">Belum ada izin penjemputan yang diajukan.</div>
            @else
                <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                    <table class="w-full border-collapse text-sm">
                        <caption class="sr-only">Daftar izin penjemputan yang diajukan</caption>
                        <thead>
                            <tr class="bg-yellow text-left text-ink">
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Tanggal jemput</th>
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Anak</th>
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Penjemput</th>
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Alasan</th>
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Status</th>
                                <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($izin as $item)
                                @php [$label, $kelas] = $badge[$item->status] ?? [$item->status, 'bg-cream text-ink']; @endphp
                                <tr wire:key="izin-{{ $item->id }}" class="border-b border-ink align-top last:border-b-0 even:bg-cream">
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">{{ $item->tanggal_jemput->translatedFormat('d M Y') }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $item->anak->nama_lengkap }}</td>
                                    <td class="px-4 py-3 font-semibold text-ink">{{ $item->nama_penjemput }}</td>
                                    <td class="max-w-xs px-4 py-3 text-muted">{{ $item->alasan }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex rounded-full border border-ink px-2.5 py-0.5 text-xs font-semibold {{ $kelas }}">{{ $label }}</span>
                                        @if ($item->sudah_dipakai)
                                            <span class="mt-1 block text-xs text-muted">Sudah dipakai</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="lihatFoto({{ $item->id }})" class="{{ $tombol }} bg-yellow">Lihat KTP</button>
                                            @if (! $item->sudah_dipakai)
                                                @if (in_array($item->status, ['menunggu', 'disetujui'], true))
                                                    <button type="button" wire:click="bukaKonfirmasi({{ $item->id }}, 'batalkan')" class="{{ $tombol }} bg-paper">Batalkan</button>
                                                @else
                                                    <button type="button" wire:click="bukaKonfirmasi({{ $item->id }}, 'hapus')" class="{{ $tombol }} bg-paper">Hapus</button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    {{-- Form pengajuan --}}
    @if ($bukaModalForm)
        <div class="fixed inset-0 z-50 flex min-h-full items-center justify-center overflow-y-auto bg-ink/60 p-4" wire:key="form-izin" x-data @keydown.escape.window="$wire.tutupForm()">
            <dialog open role="dialog" aria-modal="true" aria-labelledby="form-izin-judul" class="relative m-0 mx-auto my-auto w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
                <form wire:submit="simpan" class="grid gap-5 p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-ink/20 pb-3">
                        <h2 id="form-izin-judul" class="text-lg font-bold text-ink">Ajukan Izin Penjemputan</h2>
                        <button type="button" wire:click="tutupForm" class="rounded-ta border border-ink p-1 text-ink hover:bg-cream" aria-label="Tutup dialog">✕</button>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="anak_id" class="text-sm font-semibold text-ink">Anak</label>
                        <select id="anak_id" wire:model="anak_id" class="{{ $input }}">
                            <option value="">Pilih anak</option>
                            @foreach ($anak as $a)
                                <option value="{{ $a->id }}">{{ $a->nama_lengkap }}</option>
                            @endforeach
                        </select>
                        @error('anak_id') <p class="text-xs font-semibold text-ink">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="nama_penjemput" class="text-sm font-semibold text-ink">Nama penjemput</label>
                        <input id="nama_penjemput" type="text" wire:model="nama_penjemput" maxlength="100" class="{{ $input }}">
                        @error('nama_penjemput') <p class="text-xs font-semibold text-ink">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="tanggal_jemput" class="text-sm font-semibold text-ink">Tanggal jemput</label>
                        <input id="tanggal_jemput" type="date" wire:model="tanggal_jemput" min="{{ today()->toDateString() }}" class="{{ $input }}">
                        @error('tanggal_jemput') <p class="text-xs font-semibold text-ink">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="alasan" class="text-sm font-semibold text-ink">Alasan</label>
                        <textarea id="alasan" wire:model="alasan" rows="3" maxlength="1000" class="{{ $input }}"></textarea>
                        @error('alasan') <p class="text-xs font-semibold text-ink">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="foto_identitas" class="text-sm font-semibold text-ink">Foto KTP penjemput</label>
                        <input id="foto_identitas" type="file" wire:model="foto_identitas" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="{{ $input }} text-sm file:mr-3 file:rounded file:border-0 file:bg-yellow file:px-3 file:py-1.5 file:font-semibold">
                        <p class="text-xs text-muted">JPG atau PNG, maksimal 2 MB.</p>
                        @error('foto_identitas') <p class="text-xs font-semibold text-ink">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="tutupForm" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold text-ink hover:bg-cream">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-sm font-semibold text-ink disabled:opacity-50">Kirim Pengajuan</button>
                    </div>
                </form>
            </dialog>
        </div>
    @endif

    {{-- Konfirmasi batalkan / hapus --}}
    @if ($konfirmasiId !== null)
        <div class="fixed inset-0 z-50 flex min-h-full items-center justify-center overflow-y-auto bg-ink/60 p-4" wire:key="konfirmasi-izin" x-data @keydown.escape.window="$wire.tutupKonfirmasi()">
            <dialog open role="alertdialog" aria-modal="true" class="relative m-0 mx-auto my-auto w-full max-w-sm rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
                <div class="grid gap-4 p-6">
                    <h2 class="text-lg font-bold text-ink">{{ $konfirmasiAksi === 'hapus' ? 'Hapus izin?' : 'Batalkan izin?' }}</h2>
                    <p class="text-sm text-muted">
                        {{ $konfirmasiAksi === 'hapus' ? 'Izin beserta foto KTP-nya akan dihapus permanen.' : 'Penjemput ini tidak bisa lagi dipakai untuk menjemput anak.' }}
                    </p>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="tutupKonfirmasi" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-sm font-semibold text-ink hover:bg-cream">Kembali</button>
                        <button type="button" wire:click="jalankanKonfirmasi" wire:loading.attr="disabled" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-2 text-sm font-semibold text-ink disabled:opacity-50">{{ $konfirmasiAksi === 'hapus' ? 'Ya, hapus' : 'Ya, batalkan' }}</button>
                    </div>
                </div>
            </dialog>
        </div>
    @endif

    {{-- Pratinjau KTP --}}
    @if ($viewingFotoId !== null)
        <div class="fixed inset-0 z-50 flex min-h-full items-center justify-center overflow-y-auto bg-ink/60 p-4" wire:key="foto-izin-{{ $viewingFotoId }}" x-data @keydown.escape.window="$wire.tutupFoto()">
            <dialog open role="dialog" aria-modal="true" class="relative m-0 mx-auto my-auto w-full max-w-md rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
                <div class="grid gap-4 p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-ink/20 pb-3">
                        <h2 class="text-lg font-bold text-ink">Foto KTP {{ $viewingNama }}</h2>
                        <button type="button" wire:click="tutupFoto" class="rounded-ta border border-ink p-1 text-ink hover:bg-cream" aria-label="Tutup dialog">✕</button>
                    </div>
                    <div class="text-center">
                        @if ($previewFotoUrl)
                            <img src="{{ $previewFotoUrl }}" alt="Foto KTP {{ $viewingNama }}" class="mx-auto max-h-80 w-auto rounded border border-ink object-contain">
                        @else
                            <div class="rounded border border-dashed border-ink p-6 text-xs text-muted">Berkas foto tidak ditemukan di penyimpanan.</div>
                        @endif
                    </div>
                </div>
            </dialog>
        </div>
    @endif
</div>
