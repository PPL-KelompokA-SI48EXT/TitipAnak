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
        ]);
    }
}
