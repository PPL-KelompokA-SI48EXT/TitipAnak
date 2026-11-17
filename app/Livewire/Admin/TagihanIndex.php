<?php

namespace App\Livewire\Admin;

use App\Models\Tagihan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tagihan')]
class TagihanIndex extends Component
{
    public ?int $penerbitanId = null;

    public string $penerbitanNama = '';

    public ?int $editingTagihanId = null;

    public string $editJumlah = '';

    /**
     * @return array<string, mixed>
     */
    protected function editRules(): array
    {
        return [
            'editJumlah' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:9999999999.99'],
        ];
    }

    public function mulaiEditTagihan(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $tagihan = $this->tagihanBelumLunas($id);

        $this->editingTagihanId = $tagihan->id;
        $this->editJumlah = number_format((float) $tagihan->jumlah, 2, '.', '');
    }

    public function batalEditTagihan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $this->reset('editingTagihanId', 'editJumlah');
        $this->resetValidation('editJumlah');
    }

    public function simpanEditTagihan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);
        abort_if($this->editingTagihanId === null, 404);

        $data = $this->validate($this->editRules());
        $tagihan = $this->tagihanBelumLunas($this->editingTagihanId);
        $tagihan->update(['jumlah' => $data['editJumlah']]);

        $this->batalEditTagihan();
        session()->flash('sukses', 'Nominal tagihan berhasil diperbarui.');
    }

    protected function tagihanBelumLunas(int $id): Tagihan
    {
        return Tagihan::query()
            ->whereKey($id)
            ->where('status', 'belum_lunas')
            ->firstOrFail();
    }

    public function bukaKonfirmasiPenerbitan(int $id): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $pendaftaran = $this->pendaftaranDisetujui($id);

        $this->penerbitanId = $pendaftaran->id;
        $this->penerbitanNama = $pendaftaran->nama_orang_tua;
    }

    public function batalPenerbitan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        $this->reset('penerbitanId', 'penerbitanNama');
    }

    public function terbitkan(): void
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);
        abort_if($this->penerbitanId === null, 404);

        $pendaftaran = $this->pendaftaranDisetujui($this->penerbitanId);

        Tagihan::create([
            'pendaftaran_id' => $pendaftaran->id,
            'nomor_tagihan' => $this->nomorTagihan(),
            'jumlah' => $pendaftaran->harga,
            'status' => 'belum_lunas',
            'diterbitkan_pada' => now(),
            'jatuh_tempo' => today()->addDays(10),
        ]);

        $this->reset('penerbitanId', 'penerbitanNama');
        session()->flash('sukses', 'Tagihan berhasil diterbitkan.');
    }

    protected function pendaftaranDisetujui(int $id): object
    {
        $pendaftaran = DB::table('pendaftaran as p')
            ->join('pengguna as u', 'u.id', '=', 'p.orang_tua_id')
            ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
            ->where('p.id', $id)
            ->where('p.status', 'disetujui')
            ->whereNull('p.deleted_at')
            ->select([
                'p.id',
                'u.nama as nama_orang_tua',
                'pl.nama as nama_paket',
                'pl.harga',
            ])
            ->first();

        abort_if($pendaftaran === null, 404);

        return $pendaftaran;
    }

    protected function nomorTagihan(): string
    {
        do {
            $nomor = 'TGH-'.now()->format('Ym').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Tagihan::withTrashed()->where('nomor_tagihan', $nomor)->exists());

        return $nomor;
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('kelola tagihan'), 403);

        return view('livewire.admin.tagihan-index', [
            'pendaftaran' => DB::table('pendaftaran as p')
                ->join('pengguna as u', 'u.id', '=', 'p.orang_tua_id')
                ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
                ->where('p.status', 'disetujui')
                ->whereNull('p.deleted_at')
                ->select([
                    'p.id',
                    'u.nama as nama_orang_tua',
                    'pl.nama as nama_paket',
                    'pl.harga',
                ])
                ->orderBy('p.id')
                ->get(),
            'tagihan' => DB::table('tagihan as t')
                ->join('pendaftaran as p', 'p.id', '=', 't.pendaftaran_id')
                ->join('pengguna as u', 'u.id', '=', 'p.orang_tua_id')
                ->join('paket_layanan as pl', 'pl.id', '=', 'p.paket_layanan_id')
                ->whereNull('t.deleted_at')
                ->select([
                    't.id',
                    't.nomor_tagihan',
                    't.jumlah',
                    't.status',
                    't.diterbitkan_pada',
                    't.jatuh_tempo',
                    'p.id as pendaftaran_id',
                    'u.nama as nama_orang_tua',
                    'pl.nama as nama_paket',
                ])
                ->orderByDesc('t.diterbitkan_pada')
                ->orderByDesc('t.id')
                ->get(),
        ]);
    }
}
