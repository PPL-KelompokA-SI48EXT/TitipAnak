<div class="grid gap-8">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Penjemput Anak</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted">Daftarkan orang yang boleh menjemput anak Anda: {{ $anak->pluck('nama_lengkap')->join(', ') }}.</p>
        </div>
        @if ($anak->isNotEmpty())
            <button
                type="button"
                wire:click="bukaFormTambah"
                wire:loading.attr="disabled"
                class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
            >
                + Tambah Penjemput
            </button>
        @endif
    </header>

    @if (session('sukses'))
        <div role="status" class="rounded-ta border-[1.5px] border-ink bg-yellow px-4 py-3 text-sm font-semibold text-ink">
            {{ session('sukses') }}
        </div>
    @endif

    @if ($anak->isEmpty())
        <section class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center" aria-labelledby="tanpa-anak-heading">
            <h2 id="tanpa-anak-heading" class="text-xl font-bold text-ink">Belum ada data anak</h2>
            <p class="mt-2 text-sm text-muted">Tambahkan data anak terlebih dahulu sebelum mendaftarkan penjemput.</p>
        </section>
    @else
        <section aria-labelledby="daftar-penjemput-heading">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 id="daftar-penjemput-heading" class="text-xl font-bold text-ink">Daftar Penjemput</h2>
                    <p class="mt-1 text-sm text-muted">Orang yang terdaftar untuk menjemput anak Anda.</p>
                </div>
                <span class="inline-flex rounded-full border border-ink bg-yellow px-3 py-1 text-xs font-semibold text-ink">{{ $penjemput->count() }} orang</span>
            </div>

            @if ($penjemput->isEmpty())
                <div class="rounded-ta border-[1.5px] border-ink bg-cream px-4 py-12 text-center text-muted">
                    Belum ada penjemput yang terdaftar.
                </div>
            @else
                <div class="overflow-x-auto rounded-ta border-[1.5px] border-ink bg-paper">
                    <table class="w-full border-collapse text-sm">
                        <caption class="sr-only">Daftar orang yang boleh menjemput anak</caption>
                        <thead>
                            <tr class="bg-yellow text-left text-ink">
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Nama</th>
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Anak</th>
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Hubungan</th>
                                <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Kontak</th>
                                <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Identitas</th>
                                <th scope="col" class="whitespace-nowrap border-b-[1.5px] border-ink px-4 py-3 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penjemput as $item)
                                <tr wire:key="penjemput-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                    <td class="px-4 py-3 font-semibold text-ink">{{ $item->nama_lengkap }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $item->anak->nama_lengkap }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $item->hubungan }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-muted">{{ $item->no_hp }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-ink">
                                        @if ($item->path_foto_identitas)
                                            <button
                                                type="button"
                                                wire:click="lihatFoto({{ $item->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex min-h-8 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-2.5 py-1 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Lihat foto
                                            </button>
                                        @else
                                            <span class="text-xs text-muted">Tidak tersedia</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex flex-nowrap gap-2">
                                            <button type="button" wire:click="edit({{ $item->id }})" wire:loading.attr="disabled" wire:target="edit({{ $item->id }})" class="inline-flex min-h-9 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-3 py-1.5 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Ubah</button>
                                            <button id="hapus-penjemput-{{ $item->id }}" type="button" wire:click="bukaKonfirmasiHapus({{ $item->id }})" wire:loading.attr="disabled" wire:target="bukaKonfirmasiHapus({{ $item->id }})" class="inline-flex min-h-9 items-center justify-center rounded-ta border-[1.5px] border-ink bg-coral px-3 py-1.5 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-coral active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Hapus</button>
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

    {{-- Modal Tambah / Ubah Penjemput --}}
    @if ($bukaModalForm)
        <div
            x-data="{
                focusables() {
                    return [...$refs.dialog.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\"-1\"])')];
                },
                focusFirst() {
                    this.focusables()[0]?.focus();
                },
                restoreFocus() {
                    document.querySelector('h1')?.focus();
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
            }"
            x-init="$nextTick(() => focusFirst()); return () => restoreFocus()"
            x-on:keydown="trapFocus($event)"
            x-on:focusout.window="if (! $el.contains($event.relatedTarget)) focusFirst()"
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="form-penjemput-dialog"
            wire:keydown.escape="batalEdit"
        >
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="form-penjemput-heading"
                class="relative m-0 mx-auto my-auto w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl"
            >
                <form wire:submit="{{ $edit_id ? 'perbarui' : 'simpan' }}" class="grid gap-5 p-6">
                    <div>
                        <h2 id="form-penjemput-heading" class="text-xl font-bold text-ink">{{ $edit_id ? 'Ubah penjemput' : 'Tambah penjemput' }}</h2>
                        <p class="mt-1 text-sm text-muted">Data penjemput berlaku untuk anak yang dipilih.</p>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="anak_id" class="text-sm font-semibold text-ink">Anak</label>
                        <select id="anak_id" wire:model="anak_id" @disabled($edit_id) class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow">
                            <option value="">Pilih anak</option>
                            @foreach ($anak as $item)
                                <option value="{{ $item->id }}">{{ $item->nama_lengkap }}</option>
                            @endforeach
                        </select>
                        @error('anak_id') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <label for="nama_lengkap" class="text-sm font-semibold text-ink">Nama lengkap</label>
                            <input id="nama_lengkap" type="text" wire:model="nama_lengkap" maxlength="100" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" autocomplete="name">
                            @error('nama_lengkap') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid gap-1.5">
                            <label for="hubungan" class="text-sm font-semibold text-ink">Hubungan dengan anak</label>
                            <input id="hubungan" type="text" wire:model="hubungan" maxlength="50" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" placeholder="Contoh: Kakek">
                            @error('hubungan') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <label for="no_hp" class="text-sm font-semibold text-ink">Nomor HP</label>
                            <input id="no_hp" type="tel" wire:model="no_hp" maxlength="20" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-ink focus-visible:bg-cream focus-visible:outline-3 focus-visible:outline-yellow" autocomplete="tel">
                            @error('no_hp') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid gap-1.5">
                            <label for="foto_identitas" class="text-sm font-semibold text-ink">{{ $edit_id ? 'Foto identitas baru (opsional)' : 'Foto identitas' }}</label>
                            <input id="foto_identitas" type="file" wire:model="foto_identitas" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="w-full rounded-ta border-[1.5px] border-ink bg-paper px-3.5 py-2.5 text-sm text-ink file:mr-3 file:rounded file:border-0 file:bg-yellow file:px-3 file:py-1.5 file:font-semibold">
                            <span class="text-xs text-muted">{{ $edit_id ? 'Kosongkan untuk mempertahankan foto lama.' : 'JPG atau PNG, maksimal 2 MB.' }}</span>
                            @error('foto_identitas') <span class="text-xs font-semibold text-[#B02E12]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 pt-2">
                        <button type="button" wire:click="batalEdit" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="simpan,perbarui" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="simpan,perbarui">{{ $edit_id ? 'Simpan perubahan' : 'Simpan penjemput' }}</span>
                            <span wire:loading wire:target="simpan,perbarui">Menyimpan…</span>
                        </button>
                    </div>
                </form>
            </dialog>
        </div>
    @endif

    {{-- Modal Pratinjau Foto Identitas (Orang Tua) --}}
    @if ($viewingFotoId !== null)
        <div
            x-data
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="lihat-foto-penjemput-dialog-{{ $viewingFotoId }}"
            wire:keydown.escape="tutupLihatFoto"
        >
            <dialog
                open
                role="dialog"
                aria-modal="true"
                class="relative m-0 mx-auto my-auto w-full max-w-md rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl"
            >
                <div class="grid gap-4 p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-ink/20 pb-3">
                        <div>
                            <h2 class="text-lg font-bold text-ink">Foto Identitas Penjemput</h2>
                            <p class="text-xs text-muted">{{ $previewFotoNama }}</p>
                        </div>
                        <button type="button" wire:click="tutupLihatFoto" class="rounded-ta border border-ink p-1 text-ink hover:bg-cream">✕</button>
                    </div>

                    <div class="text-center">
                        @if ($previewFotoUrl)
                            <img src="{{ $previewFotoUrl }}" alt="Foto identitas {{ $previewFotoNama }}" class="mx-auto max-h-80 w-auto rounded border border-ink object-contain">
                        @else
                            <div class="rounded border border-dashed border-ink p-6 text-xs text-muted">Berkas foto tidak ditemukan di penyimpanan.</div>
                        @endif
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" wire:click="tutupLihatFoto" class="inline-flex min-h-10 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-4 py-2 text-xs font-semibold text-ink hover:bg-cream">Tutup</button>
                    </div>
                </div>
            </dialog>
        </div>
    @endif

    {{-- Modal Hapus Penjemput --}}
    @if ($deletingId !== null)
        <div
            x-data="{
                triggerId: 'hapus-penjemput-{{ $deletingId }}',
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
            }"
            x-init="$nextTick(() => focusFirst()); return () => restoreFocus()"
            x-on:keydown="trapFocus($event)"
            x-on:focusout.window="if (! $el.contains($event.relatedTarget)) focusFirst()"
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center bg-ink/60 p-4 overflow-y-auto"
            wire:key="hapus-penjemput-dialog-{{ $deletingId }}"
            wire:keydown.escape="batalHapus">
            <dialog
                x-ref="dialog"
                open
                role="dialog"
                aria-modal="true"
                aria-labelledby="hapus-penjemput-heading"
                aria-describedby="hapus-penjemput-description"
                class="relative m-0 mx-auto my-auto w-full max-w-lg rounded-ta border-[1.5px] border-ink bg-paper p-0 text-ink shadow-xl">
                <div class="grid gap-5 p-6">
                    <div>
                        <h2 id="hapus-penjemput-heading" class="text-xl font-bold">Nonaktifkan penjemput?</h2>
                        <p id="hapus-penjemput-description" class="mt-2 text-sm text-muted">Penjemput <strong>{{ $deletingName }}</strong> tidak akan dapat menjemput anak lagi. Data dan foto identitas tetap disimpan. Tekan Escape untuk membatalkan.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" wire:click="batalHapus" wire:loading.attr="disabled" wire:target="batalHapus,hapus" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                        <button type="button" wire:click="hapus" wire:loading.attr="disabled" wire:target="hapus" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-coral px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-coral active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="hapus">Ya, nonaktifkan</span>
                            <span wire:loading wire:target="hapus">Menonaktifkan…</span>
                        </button>
                    </div>
                </div>
            </dialog>
        </div>
    @endif
</div>
