<div class="grid gap-8">
    <header>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Rincian Tagihan</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted">Lihat rincian dan status tagihan layanan penitipan anak Anda.</p>
        </div>
    </header>

    @if (session('sukses'))
        <div role="status" class="rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-3 text-sm font-semibold text-ink">
            {{ session('sukses') }}
        </div>
    @endif

    <section aria-labelledby="daftar-tagihan-heading">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 id="daftar-tagihan-heading" class="text-xl font-bold text-ink">Daftar Tagihan</h2>
                <p class="mt-1 text-sm text-muted">Tagihan yang diterbitkan untuk pendaftaran Anda.</p>
            </div>
            <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $tagihan->count() }} tagihan</span>
        </div>

        @if ($tagihan->isEmpty())
            <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                Belum ada tagihan.
            </div>
        @else
            <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                <table class="w-full border-collapse text-sm">
                    <caption class="sr-only">Daftar rincian tagihan Anda</caption>
                    <thead>
                        <tr class="bg-yellow text-left text-ink">
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nomor tagihan</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Paket layanan</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Jumlah</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Diterbitkan</th>
                            <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Jatuh tempo</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Status</th>
                            <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tagihan as $item)
                            <tr wire:key="tagihan-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                <td class="px-4 py-3 font-semibold text-ink">{{ $item->nomor_tagihan }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->nama_paket }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">Rp {{ number_format((float) $item->jumlah, 2, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ \Carbon\Carbon::parse($item->diterbitkan_pada)->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $item->jatuh_tempo ? \Carbon\Carbon::parse($item->jatuh_tempo)->format('d/m/Y') : '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($item->status === 'lunas')
                                        <span class="inline-flex rounded-full border border-ink bg-green-200 px-2.5 py-1 text-xs font-semibold text-ink">Lunas</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-ink bg-coral/30 px-2.5 py-1 text-xs font-semibold text-ink">Belum Lunas</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($item->status === 'lunas')
                                        <span class="text-xs text-muted">Tidak diperlukan</span>
                                    @elseif ((int) $item->jumlah_pembayaran_aktif > 0)
                                        <span class="text-xs font-semibold text-muted">
                                            {{ $item->status_pembayaran_terakhir === 'terverifikasi' ? 'Pembayaran terverifikasi' : 'Menunggu verifikasi' }}
                                        </span>
                                    @else
                                        <button
                                            id="unggah-bukti-{{ $item->id }}"
                                            type="button"
                                            wire:click="bukaFormUpload({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="bukaFormUpload({{ $item->id }})"
                                            class="inline-flex min-h-9 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-3 py-1.5 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Unggah bukti
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if ($uploadingTagihanId !== null)
        <div
            x-data="{
                triggerId: 'unggah-bukti-{{ $uploadingTagihanId }}',
                focusables() {
                    return [...$refs.dialog.querySelectorAll('button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
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
            class="fixed inset-0 z-50 grid place-items-center bg-ink/60 p-4"
            wire:key="unggah-bukti-dialog-{{ $uploadingTagihanId }}"
            wire:keydown.escape="batalUpload"
        >
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="unggah-bukti-heading"
                aria-describedby="unggah-bukti-description"
                class="m-0 w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl"
            >
                <form wire:submit="unggahBukti" class="grid gap-5 p-6">
                    <div>
                        <h2 id="unggah-bukti-heading" class="text-xl font-bold">Unggah bukti pembayaran</h2>
                        <p id="unggah-bukti-description" class="mt-2 text-sm text-muted">Pilih bukti pembayaran untuk tagihan <strong>{{ $uploadingTagihanNomor }}</strong>. Format JPG, PNG, atau PDF dengan ukuran maksimal 2 MB.</p>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="buktiPembayaran" class="text-sm font-semibold text-ink">Bukti pembayaran</label>
                        <input id="buktiPembayaran" type="file" wire:model="buktiPembayaran" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-sm text-ink file:mr-3 file:rounded file:border-0 file:bg-yellow file:px-3 file:py-1.5 file:font-semibold">
                        @error('buktiPembayaran') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" wire:click="batalUpload" wire:loading.attr="disabled" wire:target="batalUpload,unggahBukti" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="unggahBukti" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="unggahBukti">Unggah bukti</span>
                            <span wire:loading wire:target="unggahBukti">Mengunggah…</span>
                        </button>
                    </div>
                </form>
            </dialog>
        </div>
    @endif
</div>
