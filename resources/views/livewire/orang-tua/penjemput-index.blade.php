<div class="grid gap-8">
    <header>
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Penjemput Anak</h1>
            <p class="mt-1 max-w-2xl text-sm text-muted">Daftarkan orang yang boleh menjemput anak Anda.</p>
        </div>
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
        <section class="rounded-ta border-[1.5px] border-ink bg-cream p-5 sm:p-6" aria-labelledby="form-penjemput-heading">
            <div class="mb-5">
                <h2 id="form-penjemput-heading" class="text-xl font-bold text-ink">{{ $edit_id ? 'Ubah penjemput' : 'Tambah penjemput' }}</h2>
                <p class="mt-1 text-sm text-muted">Data penjemput berlaku untuk anak yang dipilih.</p>
            </div>

            <form wire:submit="{{ $edit_id ? 'perbarui' : 'simpan' }}" class="grid gap-5">
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

                <div class="flex flex-wrap gap-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="simpan,perbarui" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="simpan,perbarui">{{ $edit_id ? 'Simpan perubahan' : 'Simpan penjemput' }}</span>
                        <span wire:loading wire:target="simpan,perbarui">Menyimpan…</span>
                    </button>
                    @if ($edit_id)
                        <button type="button" wire:click="batalEdit" class="inline-flex min-h-11 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-5 py-2.5 text-sm font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0">Batal</button>
                    @endif
                </div>
            </form>
        </section>

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
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Kontak</th>
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Identitas</th>
                                <th scope="col" class="border-b-[1.5px] border-ink px-4 py-3 font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($penjemput as $item)
                                <tr wire:key="penjemput-{{ $item->id }}" class="border-b border-ink last:border-b-0 even:bg-cream">
                                    <td class="px-4 py-3 font-semibold text-ink">{{ $item->nama_lengkap }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $item->anak->nama_lengkap }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $item->hubungan }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $item->no_hp }}</td>
                                    <td class="px-4 py-3 font-semibold text-ink">Tersimpan</td>
                                    <td class="px-4 py-3">
                                        <button type="button" wire:click="edit({{ $item->id }})" class="inline-flex min-h-9 items-center justify-center rounded-ta border-[1.5px] border-ink bg-paper px-3 py-1.5 text-xs font-semibold text-ink transition-transform hover:-translate-y-0.5 hover:bg-cream focus-visible:outline-3 focus-visible:outline-yellow active:translate-y-0">Ubah</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
