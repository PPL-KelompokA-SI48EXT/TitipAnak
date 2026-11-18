<div class="grid gap-8">
    <header>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Terbitkan Tagihan</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted">Terbitkan tagihan berdasarkan pendaftaran yang sudah disetujui dan tarif paket yang berlaku.</p>
        </div>
    </header>

    @if (session('sukses'))
        <div role="status" aria-live="polite" class="rounded-ta border-[1.5px] border-ink bg-mint px-4 py-3 text-sm text-ink">
            {{ session('sukses') }}
        </div>
    @endif

    <section aria-labelledby="daftar-pendaftaran-heading">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="daftar-pendaftaran-heading" class="text-xl font-bold text-ink">Pendaftaran Disetujui</h2>
                <p class="mt-1 text-sm text-muted">Pilih pendaftaran untuk menerbitkan tagihan baru.</p>
            </div>
            <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $pendaftaran->count() }} pendaftaran</span>
        </div>

        @if ($pendaftaran->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                Belum ada pendaftaran yang disetujui.
            </div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar pendaftaran yang disetujui untuk penerbitan tagihan</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Pendaftaran</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Orang tua</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Paket</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Tarif</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendaftaran as $item)
                            <tr wire:key="pendaftaran-tagihan-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="px-4 py-3 font-semibold text-ink">Pendaftaran #{{ $item->id }}</td>
                                <td class="px-4 py-3 text-ink">{{ $item->nama_orang_tua }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->nama_paket }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">Rp {{ number_format((float) $item->harga, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    <button id="terbitkan-tagihan-{{ $item->id }}" type="button" wire:click="bukaKonfirmasiPenerbitan({{ $item->id }})" wire:loading.attr="disabled" wire:target="bukaKonfirmasiPenerbitan({{ $item->id }})" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-3 py-2 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Terbitkan</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section aria-labelledby="daftar-tagihan-heading">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="daftar-tagihan-heading" class="text-xl font-bold text-ink">Daftar Tagihan</h2>
                <p class="mt-1 text-sm text-muted">Pantau jumlah dan status tagihan yang sudah diterbitkan.</p>
            </div>
            <div class="flex items-center gap-3">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="cariTagihan"
                    placeholder="Cari nomor / orang tua…"
                    aria-label="Cari tagihan"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-3 py-1.5 text-xs text-ink focus-visible:bg-cream focus-visible:outline-2 focus-visible:outline-yellow"
                >
                <span class="inline-flex whitespace-nowrap rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $tagihan->count() }} tagihan</span>
            </div>
        </div>

        @if ($tagihan->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                Belum ada tagihan yang diterbitkan.
            </div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar tagihan beserta statusnya</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nomor tagihan</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Orang tua</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Paket</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Jumlah</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Diterbitkan</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Jatuh tempo</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Status</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tagihan as $item)
                            <tr wire:key="tagihan-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">{{ $item->nomor_tagihan }}</td>
                                <td class="px-4 py-3 text-ink">{{ $item->nama_orang_tua }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->nama_paket }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">Rp {{ number_format((float) $item->jumlah, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ \Carbon\Carbon::parse($item->diterbitkan_pada)->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $item->jatuh_tempo ? \Carbon\Carbon::parse($item->jatuh_tempo)->format('d/m/Y') : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex whitespace-nowrap items-center rounded-full border border-ink px-3 py-1 text-xs font-semibold text-ink {{ $item->status === 'lunas' ? 'bg-mint' : 'bg-coral' }}">
                                        {{ $item->status === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if ($item->status === 'belum_lunas')
                                            <button id="ubah-tagihan-{{ $item->id }}" type="button" wire:click="mulaiEditTagihan({{ $item->id }})" wire:loading.attr="disabled" wire:target="mulaiEditTagihan({{ $item->id }})" class="inline-flex min-h-9 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-3 py-1.5 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Ubah nominal</button>

                                            @if ((int) $item->jumlah_pembayaran === 0)
                                                <button id="hapus-tagihan-{{ $item->id }}" type="button" wire:click="bukaKonfirmasiHapusTagihan({{ $item->id }})" wire:loading.attr="disabled" wire:target="bukaKonfirmasiHapusTagihan({{ $item->id }})" class="inline-flex min-h-9 items-center justify-center rounded-ta border-[1.5px] border-ink bg-coral px-3 py-1.5 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-coral active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Hapus</button>
                                            @else
                                                <span class="text-xs font-medium text-muted">Memiliki pembayaran</span>
                                            @endif
                                        @else
                                            <span class="text-xs font-medium text-muted">Tidak dapat dihapus</span>
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

    <section aria-labelledby="daftar-pembayaran-heading">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="daftar-pembayaran-heading" class="text-xl font-bold text-ink">Bukti Pembayaran Masuk</h2>
                <p class="mt-1 text-sm text-muted">Periksa dan cocokkan bukti pembayaran yang diunggah oleh orang tua.</p>
            </div>
            <div class="flex items-center gap-3">
                <label for="filter-pembayaran" class="text-xs font-semibold text-ink">Status:</label>
                <select
                    id="filter-pembayaran"
                    wire:model.live="filterPembayaran"
                    class="rounded-ta border-[1.5px] border-ink bg-paper px-3 py-1.5 text-xs font-semibold text-ink focus-visible:outline-3 focus-visible:outline-yellow"
                >
                    <option value="">Semua status</option>
                    <option value="menunggu">Menunggu verifikasi</option>
                    <option value="terverifikasi">Terverifikasi</option>
                    <option value="ditolak">Ditolak</option>
                </select>
                <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $daftarPembayaran->count() }} pembayaran</span>
            </div>
        </div>

        @if ($daftarPembayaran->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                Belum ada bukti pembayaran yang masuk.
            </div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar bukti pembayaran yang masuk</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nomor tagihan</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Orang tua</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Paket</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nominal tagihan</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nominal bayar</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Diajukan</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Status</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftarPembayaran as $item)
                            <tr wire:key="pembayaran-masuk-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">{{ $item->nomor_tagihan }}</td>
                                <td class="px-4 py-3 text-ink">{{ $item->nama_orang_tua }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->nama_paket }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">Rp {{ number_format((float) $item->jumlah_tagihan, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">
                                    <span>Rp {{ number_format((float) $item->jumlah_bayar, 2, ',', '.') }}</span>
                                    @if ((float) $item->jumlah_bayar !== (float) $item->jumlah_tagihan)
                                        <span class="ml-1 inline-flex items-center rounded border border-[#B02E12] bg-coral/20 px-1.5 py-0.5 text-[10px] font-bold text-[#B02E12]" title="Nominal bukti berbeda dengan nominal tagihan">
                                            Beda nominal
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ \Carbon\Carbon::parse($item->diajukan_pada)->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($item->status_pembayaran === 'terverifikasi')
                                        <span class="inline-flex whitespace-nowrap items-center rounded-full border border-ink bg-mint px-2.5 py-1 text-xs font-semibold text-ink">Terverifikasi</span>
                                    @elseif ($item->status_pembayaran === 'ditolak')
                                        <span class="inline-flex whitespace-nowrap items-center rounded-full border border-ink bg-coral px-2.5 py-1 text-xs font-semibold text-ink">Ditolak</span>
                                    @else
                                        <span class="inline-flex whitespace-nowrap items-center rounded-full border border-ink bg-yellow px-2.5 py-1 text-xs font-semibold text-ink">Menunggu</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="grid grid-cols-2 gap-1.5 w-max">
                                        <button
                                            id="lihat-bukti-{{ $item->id }}"
                                            type="button"
                                            wire:click="lihatBukti({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="lihatBukti({{ $item->id }})"
                                            class="inline-flex min-h-8 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-2.5 py-1 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Lihat bukti
                                        </button>
                                        <button
                                            id="unduh-bukti-{{ $item->id }}"
                                            type="button"
                                            wire:click="unduhBukti({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="unduhBukti({{ $item->id }})"
                                            class="inline-flex min-h-8 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-2.5 py-1 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Unduh
                                        </button>

                                        @if ($item->status_pembayaran === 'menunggu')
                                            <button
                                                id="verifikasi-pembayaran-{{ $item->id }}"
                                                type="button"
                                                wire:click="bukaKonfirmasiVerifikasi({{ $item->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="bukaKonfirmasiVerifikasi({{ $item->id }})"
                                                class="inline-flex min-h-8 items-center justify-center rounded-ta border-[1.5px] border-ink bg-mint px-2.5 py-1 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-mint active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Verifikasi
                                            </button>
                                            <button
                                                id="tolak-pembayaran-{{ $item->id }}"
                                                type="button"
                                                wire:click="bukaKonfirmasiTolak({{ $item->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="bukaKonfirmasiTolak({{ $item->id }})"
                                                class="inline-flex min-h-8 items-center justify-center rounded-ta border-[1.5px] border-ink bg-coral px-2.5 py-1 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-coral active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Tolak
                                            </button>
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

    @if ($deletingTagihanId !== null)
        <div
            x-data="{
                triggerId: 'hapus-tagihan-{{ $deletingTagihanId }}',
                focusables() {
                    return [...$refs.dialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
                },
                focusFirst() {
                    this.focusables()[0]?.focus();
                },
                restoreFocus() {
                    document.getElementById(this.triggerId)?.focus();
                    document.getElementById(this.triggerId) || document.querySelector('h1')?.focus();
                },
                trapFocus(event) {
                    if (event.key !== 'Tab') return;

                    const elements = this.focusables();
                    if (! elements.length) return;

                    const first = elements[0];
                    const last = elements[elements.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (! event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            }
            x-init="$nextTick(() => focusFirst()); return () => restoreFocus()"
            x-on:keydown="trapFocus($event)"
            x-on:focusout.window="if (! $el.contains($event.relatedTarget)) focusFirst()"
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="hapus-tagihan-dialog-{{ $deletingTagihanId }}"
            wire:keydown.escape="batalHapusTagihan">
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="hapus-tagihan-heading"
                aria-describedby="hapus-tagihan-description"
                class="relative m-0 mx-auto my-auto w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
                <div class="grid gap-5 p-6">
                    <div>
                        <h2 id="hapus-tagihan-heading" class="text-xl font-bold">Hapus tagihan?</h2>
                        <p id="hapus-tagihan-description" class="mt-2 text-sm text-muted">Tagihan <strong>{{ $deletingTagihanNomor }}</strong> akan dihapus dari daftar aktif. Data tetap disimpan sebagai riwayat. Tekan Escape untuk membatalkan.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" wire:click="batalHapusTagihan" wire:loading.attr="disabled" wire:target="batalHapusTagihan,hapusTagihan" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                        <button type="button" wire:click="hapusTagihan" wire:loading.attr="disabled" wire:target="hapusTagihan" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-coral px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-coral active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="hapusTagihan">Ya, hapus</span>
                            <span wire:loading wire:target="hapusTagihan">Menghapus…</span>
                        </button>
                    </div>
                </div>
            </dialog>
        </div>
    @endif

    @if ($editingTagihanId !== null)
        <div
            x-data="{
                triggerId: 'ubah-tagihan-{{ $editingTagihanId }}',
                focusables() {
                    return [...$refs.dialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
                },
                focusFirst() {
                    this.focusables()[0]?.focus();
                },
                restoreFocus() {
                    document.getElementById(this.triggerId)?.focus();
                    document.getElementById(this.triggerId) || document.querySelector('h1')?.focus();
                },
                trapFocus(event) {
                    if (event.key !== 'Tab') return;

                    const elements = this.focusables();
                    if (! elements.length) return;

                    const first = elements[0];
                    const last = elements[elements.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (! event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            }
            x-init="$nextTick(() => focusFirst()); return () => restoreFocus()"
            x-on:keydown="trapFocus($event)"
            x-on:focusout.window="if (! $el.contains($event.relatedTarget)) focusFirst()"
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="ubah-tagihan-dialog-{{ $editingTagihanId }}"
            wire:keydown.escape="batalEditTagihan">
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="ubah-tagihan-heading"
                class="relative m-0 mx-auto my-auto w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
                <form wire:submit="simpanEditTagihan" class="grid gap-5 p-6">
                    <div>
                        <h2 id="ubah-tagihan-heading" class="text-xl font-bold">Ubah nominal tagihan</h2>
                        <p class="mt-2 text-sm text-muted">Perbarui nominal tagihan yang belum lunas jika terdapat kesalahan.</p>
                    </div>
                    <div class="grid gap-1.5">
                        <label for="editJumlah" class="text-sm font-semibold text-ink">Nominal tagihan</label>
                        <input id="editJumlah" type="number" wire:model="editJumlah" min="1" max="9999999999.99" step="0.01" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" inputmode="decimal">
                        @error('editJumlah') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" wire:click="batalEditTagihan" wire:loading.attr="disabled" wire:target="batalEditTagihan,simpanEditTagihan" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="simpanEditTagihan" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="simpanEditTagihan">Simpan perubahan</span>
                            <span wire:loading wire:target="simpanEditTagihan">Menyimpan…</span>
                        </button>
                    </div>
                </form>
            </dialog>
        </div>
    @endif

    @if ($penerbitanId !== null)
        <div
            x-data="{
                triggerId: 'terbitkan-tagihan-{{ $penerbitanId }}',
                focusables() {
                    return [...$refs.dialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
                },
                focusFirst() {
                    this.focusables()[0]?.focus();
                },
                restoreFocus() {
                    document.getElementById(this.triggerId)?.focus();
                    document.getElementById(this.triggerId) || document.querySelector('h1')?.focus();
                },
                trapFocus(event) {
                    if (event.key !== 'Tab') return;

                    const elements = this.focusables();
                    if (! elements.length) return;

                    const first = elements[0];
                    const last = elements[elements.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (! event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            }
            x-init="$nextTick(() => focusFirst()); return () => restoreFocus()"
            x-on:keydown="trapFocus($event)"
            x-on:focusout.window="if (! $el.contains($event.relatedTarget)) focusFirst()"
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="terbitkan-tagihan-dialog-{{ $penerbitanId }}"
            wire:keydown.escape="batalPenerbitan">
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="terbitkan-tagihan-heading"
                aria-describedby="terbitkan-tagihan-description"
                class="relative m-0 mx-auto my-auto w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
                <div class="grid gap-5 p-6">
                    <div>
                        <h2 id="terbitkan-tagihan-heading" class="text-xl font-bold">Terbitkan tagihan?</h2>
                        <p id="terbitkan-tagihan-description" class="mt-2 text-sm text-muted">Tagihan untuk orang tua <strong>{{ $penerbitanNama }}</strong> akan dibuat dengan tarif paket saat ini dan jatuh tempo 10 hari dari tanggal penerbitan.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" wire:click="batalPenerbitan" wire:loading.attr="disabled" wire:target="batalPenerbitan,terbitkan" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                        <button type="button" wire:click="terbitkan" wire:loading.attr="disabled" wire:target="terbitkan" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="terbitkan">Ya, terbitkan</span>
                            <span wire:loading wire:target="terbitkan">Menerbitkan…</span>
                        </button>
                    </div>
                </div>
            </dialog>
        </div>
    @endif

    @if ($viewingPembayaranId !== null && $detailPembayaran !== null)
        <div
            x-data="{
                triggerId: 'lihat-bukti-{{ $viewingPembayaranId }}',
                focusables() {
                    return [...$refs.dialog.querySelectorAll('button:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
                },
                focusFirst() {
                    this.focusables()[0]?.focus();
                },
                restoreFocus() {
                    document.getElementById(this.triggerId)?.focus();
                },
                trapFocus(event) {
                    if (event.key !== 'Tab') return;

                    const elements = this.focusables();
                    if (! elements.length) return;

                    const first = elements[0];
                    const last = elements[elements.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (! event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            }
            x-init="$nextTick(() => focusFirst()); return () => restoreFocus()"
            x-on:keydown="trapFocus($event)"
            x-on:focusout.window="if (! $el.contains($event.relatedTarget)) focusFirst()"
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="lihat-bukti-dialog-{{ $viewingPembayaranId }}"
            wire:keydown.escape="tutupLihatBukti"
        >
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="lihat-bukti-heading"
                aria-describedby="lihat-bukti-description"
                class="relative m-0 mx-auto my-auto w-full max-w-2xl rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl"
            >
                <div class="grid gap-5 p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-ink/20 pb-4">
                        <div>
                            <h2 id="lihat-bukti-heading" class="text-xl font-bold">Bukti Pembayaran Masuk</h2>
                            <p id="lihat-bukti-description" class="mt-1 text-sm text-muted">Rincian pembayaran untuk tagihan <strong>{{ $detailPembayaran->nomor_tagihan }}</strong>.</p>
                        </div>
                        <button
                            type="button"
                            wire:click="tutupLihatBukti"
                            class="rounded-ta border border-ink p-1 text-ink hover:bg-cream focus-visible:outline-2 focus-visible:outline-yellow"
                            aria-label="Tutup dialog"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Data Rincian untuk Pencocokan --}}
                    <div class="grid grid-cols-1 gap-3 rounded-ta border border-ink bg-cream p-4 text-xs sm:grid-cols-2">
                        <div>
                            <span class="text-muted">Orang Tua:</span>
                            <p class="font-semibold text-ink">{{ $detailPembayaran->nama_orang_tua }} ({{ $detailPembayaran->email_orang_tua }})</p>
                        </div>
                        <div>
                            <span class="text-muted">Paket Layanan:</span>
                            <p class="font-semibold text-ink">{{ $detailPembayaran->nama_paket }}</p>
                        </div>
                        <div>
                            <span class="text-muted">Nominal Tagihan:</span>
                            <p class="font-bold text-ink">Rp {{ number_format((float) $detailPembayaran->jumlah_tagihan, 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-muted">Nominal Bukti Pembayaran:</span>
                            <p class="font-bold text-ink">Rp {{ number_format((float) $detailPembayaran->jumlah_bayar, 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <span class="text-muted">Waktu Pengajuan:</span>
                            <p class="font-semibold text-ink">{{ \Carbon\Carbon::parse($detailPembayaran->diajukan_pada)->format('d F Y, H:i') }}</p>
                        </div>
                        <div>
                            <span class="text-muted">Status Pembayaran:</span>
                            <p class="font-semibold">
                                @if ($detailPembayaran->status_pembayaran === 'terverifikasi')
                                    <span class="inline-flex rounded-full border border-ink bg-mint px-2 py-0.5 text-xs font-semibold text-ink">Terverifikasi</span>
                                @elseif ($detailPembayaran->status_pembayaran === 'ditolak')
                                    <span class="inline-flex rounded-full border border-ink bg-coral px-2 py-0.5 text-xs font-semibold text-ink">Ditolak</span>
                                @else
                                    <span class="inline-flex rounded-full border border-ink bg-yellow px-2 py-0.5 text-xs font-semibold text-ink">Menunggu Verifikasi</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Pratinjau Berkas --}}
                    <div class="grid gap-2">
                        <span class="text-sm font-semibold text-ink">Berkas Bukti Transfer:</span>
                        @if (! $detailPembayaran->file_exists)
                            <div class="rounded-ta border border-dashed border-ink bg-paper p-8 text-center text-sm text-muted">
                                Berkas bukti pembayaran fisik tidak ditemukan di penyimpanan lokal.
                            </div>
                        @elseif ($detailPembayaran->is_image)
                            <div class="overflow-hidden rounded-ta border-[1.5px] border-ink bg-paper p-2 text-center">
                                <img
                                    src="{{ $detailPembayaran->preview_url }}"
                                    alt="Bukti pembayaran tagihan {{ $detailPembayaran->nomor_tagihan }}"
                                    class="mx-auto max-h-96 w-auto max-w-full rounded object-contain"
                                >
                            </div>
                        @elseif ($detailPembayaran->is_pdf)
                            <div class="flex items-center justify-between rounded-ta border border-ink bg-paper p-4">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">📄</span>
                                    <div>
                                        <p class="text-sm font-semibold text-ink">Dokumen Bukti PDF</p>
                                        <p class="text-xs text-muted">Berkas bukti diunggah dalam format dokumen PDF.</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="unduhBukti({{ $detailPembayaran->id }})"
                                    class="inline-flex min-h-9 items-center justify-center rounded-ta border border-ink bg-yellow px-3 py-1.5 text-xs font-semibold text-ink hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-yellow"
                                >
                                    Unduh dokumen
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink/20 pt-4">
                        <div class="flex flex-wrap gap-2">
                            @if ($detailPembayaran->status_pembayaran === 'menunggu')
                                <button
                                    type="button"
                                    wire:click="bukaKonfirmasiVerifikasi({{ $detailPembayaran->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-mint px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-mint active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Verifikasi pembayaran
                                </button>
                                <button
                                    type="button"
                                    wire:click="bukaKonfirmasiTolak({{ $detailPembayaran->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-coral px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-coral active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Tolak pembayaran
                                </button>
                            @endif
                        </div>

                        <div class="flex flex-wrap justify-end gap-3">
                            @if ($detailPembayaran->file_exists)
                                <button
                                    type="button"
                                    wire:click="unduhBukti({{ $detailPembayaran->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Unduh berkas bukti
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="tutupLihatBukti"
                                class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </dialog>
        </div>
    @endif

    @if ($verifyingPembayaranId !== null)
        <div
            x-data="{
                triggerId: 'verifikasi-pembayaran-{{ $verifyingPembayaranId }}',
                focusables() {
                    return [...$refs.dialog.querySelectorAll('button:not([disabled]), input:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
                },
                focusFirst() {
                    this.focusables()[0]?.focus();
                },
                restoreFocus() {
                    document.getElementById(this.triggerId)?.focus();
                },
                trapFocus(event) {
                    if (event.key !== 'Tab') return;

                    const elements = this.focusables();
                    if (! elements.length) return;

                    const first = elements[0];
                    const last = elements[elements.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (! event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            }
            x-init="$nextTick(() => focusFirst()); return () => restoreFocus()"
            x-on:keydown="trapFocus($event)"
            x-on:focusout.window="if (! $el.contains($event.relatedTarget)) focusFirst()"
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="verifikasi-pembayaran-dialog-{{ $verifyingPembayaranId }}"
            wire:keydown.escape="batalVerifikasi"
        >
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="verifikasi-heading"
                aria-describedby="verifikasi-description"
                class="relative m-0 mx-auto my-auto w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl"
            >
                <form wire:submit="verifikasiPembayaran" class="grid gap-5 p-6">
                    <div>
                        <h2 id="verifikasi-heading" class="text-xl font-bold">Verifikasi pembayaran?</h2>
                        <p id="verifikasi-description" class="mt-2 text-sm text-muted">
                            Pembayaran untuk tagihan <strong>{{ $verifyingNomorTagihan }}</strong> sebesar <strong>Rp {{ number_format((float) $verifyingJumlah, 2, ',', '.') }}</strong> akan diverifikasi. Status tagihan akan otomatis berubah menjadi <strong>Lunas</strong>.
                        </p>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="verifyingCatatan" class="text-sm font-semibold text-ink">Catatan verifikasi (opsional)</label>
                        <input
                            id="verifyingCatatan"
                            type="text"
                            wire:model="verifyingCatatan"
                            maxlength="255"
                            placeholder="Contoh: Pembayaran telah dicocokkan dengan mutasi rekening"
                            class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-sm text-ink focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow"
                        >
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" wire:click="batalVerifikasi" wire:loading.attr="disabled" wire:target="batalVerifikasi,verifikasiPembayaran" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="verifikasiPembayaran" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-mint px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-mint active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="verifikasiPembayaran">Ya, verifikasi & lunaskan</span>
                            <span wire:loading wire:target="verifikasiPembayaran">Memproses…</span>
                        </button>
                    </div>
                </form>
            </dialog>
        </div>
    @endif

    @if ($rejectingPembayaranId !== null)
        <div
            x-data="{
                triggerId: 'tolak-pembayaran-{{ $rejectingPembayaranId }}',
                focusables() {
                    return [...$refs.dialog.querySelectorAll('button:not([disabled]), input:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
                },
                focusFirst() {
                    this.focusables()[0]?.focus();
                },
                restoreFocus() {
                    document.getElementById(this.triggerId)?.focus();
                },
                trapFocus(event) {
                    if (event.key !== 'Tab') return;

                    const elements = this.focusables();
                    if (! elements.length) return;

                    const first = elements[0];
                    const last = elements[elements.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (! event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            }
            x-init="$nextTick(() => focusFirst()); return () => restoreFocus()"
            x-on:keydown="trapFocus($event)"
            x-on:focusout.window="if (! $el.contains($event.relatedTarget)) focusFirst()"
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="tolak-pembayaran-dialog-{{ $rejectingPembayaranId }}"
            wire:keydown.escape="batalTolak"
        >
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="tolak-heading"
                aria-describedby="tolak-description"
                class="relative m-0 mx-auto my-auto w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl"
            >
                <form wire:submit="tolakPembayaran" class="grid gap-5 p-6">
                    <div>
                        <h2 id="tolak-heading" class="text-xl font-bold">Tolak bukti pembayaran?</h2>
                        <p id="tolak-description" class="mt-2 text-sm text-muted">
                            Bukti pembayaran untuk tagihan <strong>{{ $rejectingNomorTagihan }}</strong> akan ditolak. Orang tua dapat melihat alasan penolakan dan mengunggah bukti baru.
                        </p>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="rejectingCatatan" class="text-sm font-semibold text-ink">Alasan penolakan <span class="text-[#B02E12]">*</span></label>
                        <textarea
                            id="rejectingCatatan"
                            wire:model="rejectingCatatan"
                            rows="3"
                            maxlength="500"
                            placeholder="Contoh: Nominal transfer kurang atau bukti tidak terbaca jelas"
                            class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-sm text-ink focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow"
                        ></textarea>
                        @error('rejectingCatatan') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" wire:click="batalTolak" wire:loading.attr="disabled" wire:target="batalTolak,tolakPembayaran" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="tolakPembayaran" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-coral px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-coral active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="tolakPembayaran">Ya, tolak pembayaran</span>
                            <span wire:loading wire:target="tolakPembayaran">Memproses…</span>
                        </button>
                    </div>
                </form>
            </dialog>
        </div>
    @endif
</div>
