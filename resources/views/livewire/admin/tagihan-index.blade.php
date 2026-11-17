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
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="daftar-tagihan-heading" class="text-xl font-bold text-ink">Daftar Tagihan</h2>
                <p class="mt-1 text-sm text-muted">Pantau jumlah dan status tagihan yang sudah diterbitkan.</p>
            </div>
            <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $tagihan->count() }} tagihan</span>
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
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->nomor_tagihan }}</td>
                                <td class="px-4 py-3 text-ink">{{ $item->nama_orang_tua }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->nama_paket }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">Rp {{ number_format((float) $item->jumlah, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ \Carbon\Carbon::parse($item->diterbitkan_pada)->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $item->jatuh_tempo ? \Carbon\Carbon::parse($item->jatuh_tempo)->format('d/m/Y') : '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full border border-ink px-3 py-1 text-xs font-semibold text-ink {{ $item->status === 'lunas' ? 'bg-mint' : 'bg-coral' }}">
                                        {{ $item->status === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($item->status === 'belum_lunas')
                                            <button id="ubah-tagihan-{{ $item->id }}" type="button" wire:click="mulaiEditTagihan({{ $item->id }})" wire:loading.attr="disabled" wire:target="mulaiEditTagihan({{ $item->id }})" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-3 py-2 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Ubah nominal</button>

                                            @if ((int) $item->jumlah_pembayaran === 0)
                                                <button id="hapus-tagihan-{{ $item->id }}" type="button" wire:click="bukaKonfirmasiHapusTagihan({{ $item->id }})" wire:loading.attr="disabled" wire:target="bukaKonfirmasiHapusTagihan({{ $item->id }})" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-coral px-3 py-2 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-coral active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Hapus</button>
                                            @else
                                                <span class="self-center text-xs text-muted">Memiliki pembayaran</span>
                                            @endif
                                        @else
                                            <span class="self-center text-xs text-muted">Tidak dapat dihapus</span>
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
            class="fixed inset-0 z-50 grid place-items-center bg-ink/60 p-4"
            wire:key="hapus-tagihan-dialog-{{ $deletingTagihanId }}"
            wire:keydown.escape="batalHapusTagihan">
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="hapus-tagihan-heading"
                aria-describedby="hapus-tagihan-description"
                class="m-0 w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
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
            class="fixed inset-0 z-50 grid place-items-center bg-ink/60 p-4"
            wire:key="ubah-tagihan-dialog-{{ $editingTagihanId }}"
            wire:keydown.escape="batalEditTagihan">
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="ubah-tagihan-heading"
                class="m-0 w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
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
            class="fixed inset-0 z-50 grid place-items-center bg-ink/60 p-4"
            wire:key="terbitkan-tagihan-dialog-{{ $penerbitanId }}"
            wire:keydown.escape="batalPenerbitan">
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="terbitkan-tagihan-heading"
                aria-describedby="terbitkan-tagihan-description"
                class="m-0 w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
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
</div>
